<?php

declare(strict_types=1);

namespace Rexpl\Libsql;

use Phrity\Net\UriFactory;
use Rexpl\Libsql\Contracts\ClientMessage;
use Rexpl\Libsql\Contracts\Request;
use Rexpl\Libsql\Contracts\Response;
use Rexpl\Libsql\Contracts\ServerMessage;
use Rexpl\Libsql\Contracts\WebsocketDriver;
use Rexpl\Libsql\Exception\ConnectionException;
use Rexpl\Libsql\Exception\LibsqlException;
use Rexpl\Libsql\Hrana\Message\Client\HelloMessage;
use Rexpl\Libsql\Hrana\Message\Client\RequestMessage;
use Rexpl\Libsql\Hrana\Message\Server\HelloErrorMessage;
use Rexpl\Libsql\Hrana\Message\Server\HelloOkMessage;
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
use Rexpl\Libsql\Websockets\WebsocketDriverFactory;

class LibsqlConnection
{
    /**
     * @var \Rexpl\Libsql\Contracts\WebsocketDriver
     */
    protected WebsocketDriver $driver;

    /**
     * @var \Rexpl\Libsql\Hrana\Version
     */
    protected Version $version;

    protected int $streamId = 1;
    protected int $requestId = 1;
    protected bool $streamIsOpen = false;

    public ?string $lastInsertedRowId = null;

    /**
     * @param \Rexpl\Libsql\Contracts\WebsocketDriver|null $driver
     */
    public function __construct(?WebsocketDriver $driver)
    {
        $this->driver = $driver ?: WebsocketDriverFactory::create();
    }

    /**
     * @param string $url
     * @param \SensitiveParameterValue $token
     * @param bool $secure
     * @param string $libraryVersion
     *
     * @return Version The negotiated protocol version.
     */
    public function connect(string $url, \SensitiveParameterValue $token, bool $secure, string $libraryVersion): Version
    {
        $uri = (new UriFactory())->createUri($url);

        if ($uri->getScheme() === 'libsql') {
            $uri = $uri->withScheme($secure ? 'wss' : 'ws');
        }

        $response = $this->driver->connect($uri, [
            'Sec-WebSocket-Protocol' => 'hrana3',
            'X-Libsql-Client-Version' => \sprintf('libsql-remote-php-%s', $libraryVersion),
        ]);

        if (
            $response->hasHeader('Sec-WebSocket-Protocol')
            && $response->getHeader('Sec-WebSocket-Protocol')[0] === 'hrana3'
        ) {
            $this->version = Version::HRANA_3;
        } else {
            $this->disconnect(false);
            throw new ConnectionException('Unsupported libsql server version.');
        }

        $message = $this->send(new HelloMessage($token));

        if ($message instanceof HelloOkMessage) {
            $this->openStream();
            return $this->version;
        }

        $this->disconnect(false);

        if ($message instanceof HelloErrorMessage) {
            $message->error->throw(ConnectionException::class);
        }

        throw new \LogicException('Invalid protocol implementation.');
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

        $this->disconnect(false);

        if (!$response instanceof CloseStreamResponse) {
            throw new LibsqlException('Failed to close libsql stream, after failing to open libsql stream.');
        }

        throw new LibsqlException('Failed to open libsql stream.');
    }

    public function isConnected(): bool
    {
        return $this->driver->isConnected();
    }

    public function getStreamId(): int
    {
        return $this->streamId;
    }

    public function disconnect(bool $closeStreams = true): void
    {
        if (!$this->driver->isConnected()) {
            return;
        }

        if ($closeStreams && $this->streamIsOpen) {

            $response = $this->request(new CloseStreamRequest($this->streamId));

            if (!$response instanceof CloseStreamResponse) {
                throw new LibsqlException('Failed to close libsql stream.');
            }
        }

        $this->driver->disconnect();
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
        $message = $this->send(new RequestMessage($request, $this->requestId++));

        if ($message instanceof ResponseOkMessage) {
            return $message->response;
        }

        if ($message instanceof ResponseErrorMessage) {
            $message->error->throw(LibsqlException::class);
        }

        throw new \LogicException('Invalid protocol implementation.');
    }

    /**
     * @param \Rexpl\Libsql\Contracts\ClientMessage $message
     *
     * @return \Rexpl\Libsql\Contracts\ServerMessage
     */
    public function send(ClientMessage $message): ServerMessage
    {
        $preparedMessage = $message->getMessage($this->version);
        $jsonMessage = \json_encode($preparedMessage);

        $receivedJsonMessage = $this->driver->textMessage($jsonMessage);

        $receivedMessage = \json_decode($receivedJsonMessage);

        return match ($receivedMessage->type) {
            'hello_ok' => HelloOkMessage::parseMessage($this->version, $receivedMessage),
            'hello_error' => HelloErrorMessage::parseMessage($this->version, $receivedMessage),
            'response_ok' => ResponseOkMessage::parseMessage($this->version, $receivedMessage),
            'response_error' => ResponseErrorMessage::parseMessage($this->version, $receivedMessage),
            default => throw new \LogicException('Invalid protocol implementation.'),
        };
    }

    public function __destruct()
    {
        $this->disconnect();
    }
}