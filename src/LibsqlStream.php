<?php

declare(strict_types=1);

namespace Rexpl\Libsql;

use Rexpl\Libsql\Contracts\Request;
use Rexpl\Libsql\Contracts\Response;
use Rexpl\Libsql\Exception\LibsqlException;
use Rexpl\Libsql\Hrana\Message\Client\RequestMessage;
use Rexpl\Libsql\Hrana\Message\Server\ResponseErrorMessage;
use Rexpl\Libsql\Hrana\Message\Server\ResponseOkMessage;
use Rexpl\Libsql\Hrana\Request\CloseStreamRequest;
use Rexpl\Libsql\Hrana\Request\ExecuteRequest;
use Rexpl\Libsql\Hrana\Request\OpenStreamRequest;
use Rexpl\Libsql\Hrana\Response\CloseStreamResponse;
use Rexpl\Libsql\Hrana\Response\OpenStreamResponse;
use Rexpl\Libsql\Hrana\Statement;
use Rexpl\Libsql\Hrana\StatementResult;
use Rexpl\Libsql\Hrana\Version;

class LibsqlStream
{
    /**
     * Stream id of this stream.
     *
     * @var int
     */
    public readonly int $streamId;

    /**
     * Request id incrementer.
     *
     * @var int
     */
    protected int $requestId = 1;

    /**
     * Is stored here to allow prepared statements to share this value.
     *
     * @var string|null
     */
    public ?string $lastInsertedRowId = null;

    /**
     * Whether the stream was successfully opened. Used when disconnecting to know whether we should close the stream or not.
     *
     * @var bool
     */
    protected bool $streamIsOpen = false;

    /**
     * @param \Rexpl\Libsql\LibsqlConnection $connection
     * @param \Rexpl\Libsql\Hrana\Version $protocolVersion
     */
    public function __construct(protected LibsqlConnection $connection, public readonly Version $protocolVersion)
    {
        $this->streamId = $this->connection->getNextStreamId();
        $this->openStream();
    }

    protected function openStream(): void
    {
        $response = $this->request(new OpenStreamRequest($this->streamId));

        if ($response instanceof OpenStreamResponse) {
            $this->streamIsOpen = true;
            return;
        }

        // The client should close even streams for which the open_stream request returned an error.
        $response = $this->request(new CloseStreamRequest($this->streamId));

        if (!$response instanceof CloseStreamResponse) {
            throw new LibsqlException('Failed to close libsql stream, after failing to open libsql stream.');
        }

        throw new LibsqlException('Failed to open libsql stream.');
    }

    protected function closeStream(): void
    {
        if (!$this->streamIsOpen) {
            return;
        }

        $response = $this->request(new CloseStreamRequest($this->streamId));

        if (!$response instanceof CloseStreamResponse) {
            throw new LibsqlException('Failed to close libsql stream.');
        }

        $this->streamIsOpen = false;
    }

    /**
     * Executes the given statement.
     *
     * @param \Rexpl\Libsql\Hrana\Statement $statement
     *
     * @return \Rexpl\Libsql\Hrana\StatementResult
     */
    public function executeStatement(Statement $statement): StatementResult
    {
        $request = new ExecuteRequest($this->streamId, $statement);

        /** @var \Rexpl\Libsql\Hrana\Response\ExecuteResponse $response */
        $response = $this->request($request);

        if ($response->statementResult->lastInsertedRowId !== null) {
            $this->lastInsertedRowId = $response->statementResult->lastInsertedRowId;
        }

        return $response->statementResult;
    }

    /**
     * @param \Rexpl\Libsql\Contracts\Request $request
     *
     * @return \Rexpl\Libsql\Contracts\Response
     */
    public function request(Request $request): Response
    {
        $message = $this->connection->send(new RequestMessage($request, $this->requestId++));

        if ($message instanceof ResponseOkMessage) {
            return $message->response;
        }

        if ($message instanceof ResponseErrorMessage) {
            $message->error->throw(LibsqlException::class);
        }

        throw new \LogicException('Invalid protocol implementation.');
    }

    public function isConnected(): bool
    {
        return $this->connection->isConnected();
    }

    public function makeNewStream(): static
    {
        return new LibsqlStream($this->connection, $this->protocolVersion);
    }

    public function __destruct()
    {
        $this->closeStream();
    }
}