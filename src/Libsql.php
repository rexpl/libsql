<?php

declare(strict_types=1);

namespace Rexpl\Libsql;

use Rexpl\Libsql\Contracts\WebsocketDriver;
use Rexpl\Libsql\Hrana\Request\GetAutoCommitRequest;
use Rexpl\Libsql\Hrana\Statement;
use Rexpl\Libsql\Hrana\Version;

class Libsql
{
    /**
     * Library version.
     *
     * @var int
     */
    public const VERSION = '0.2.0';

    public const FETCH_ASSOC = 2;
    public const FETCH_NUM = 3;
    public const FETCH_OBJ = 5;
    public const FETCH_CLASS = 8;
    public const FETCH_FUNC = 10;

    /**
     * @var \Rexpl\Libsql\LibsqlStream
     */
    protected LibsqlStream $stream;

    /**
     * @var int
     */
    protected int $defaultFetchMode = Libsql::FETCH_ASSOC;

    /**
     * @var mixed|null
     */
    protected mixed $fetchClassOrCallable = null;

    /**
     * @var array
     */
    protected array $fetchConstructorArguments = [];

    /**
     * @param string $url
     * @param string|null $token
     * @param bool $secure
     * @param \Rexpl\Libsql\Contracts\WebsocketDriver|null $driver
     * @param \Rexpl\Libsql\LibsqlStream|null $stream
     */
    public function __construct(
        string $url = 'libsql://127.0.0.1:8080',
        #[\SensitiveParameter] ?string $token = null,
        bool $secure = true,
        ?WebsocketDriver $driver = null,
        ?LibsqlStream $stream = null,
    ) {
        // If the stream is null we are opening the connection.
        if ($stream === null) {
            $token = new \SensitiveParameterValue($token);

            $connection = new LibsqlConnection($driver);
            $version = $connection->connect($url, $token, $secure);

            $stream = new LibsqlStream($connection, $version);
        }

        $this->stream = $stream;
    }

    /**
     * Sets the default fetch mode for this stream and any created streams.
     *
     * @param int $mode The fetch mode see {@see \Rexpl\Libsql\Libsql::FETCH_}* constants.
     * @param string|callable|null $classOrCallable Name of the created class for {@see \Rexpl\Libsql\Libsql::FETCH_CLASS},
     * or the callable for {@see \Rexpl\Libsql\Libsql::FETCH_FUNC}.
     * @param array $constructorArgs Elements of this array are passed to the constructor ({@see \Rexpl\Libsql\Libsql::FETCH_CLASS}).
     */
    public function setDefaultFetchMode(int $mode, string|callable|null $classOrCallable = null, array $constructorArgs = []): void
    {
        $this->defaultFetchMode = $mode;
        $this->fetchClassOrCallable = $classOrCallable;
        $this->fetchConstructorArguments = $constructorArgs;
    }

    /**
     * Initiates a transaction, turns off autocommit mode.
     *
     * @return bool
     */
    public function beginTransaction(): bool
    {
        $this->stream->executeStatement(new Statement('BEGIN TRANSACTION', null, false));

        return true;
    }

    /**
     * Commits a transaction.
     *
     * @return bool
     */
    public function commit(): bool
    {
        $this->stream->executeStatement(new Statement('COMMIT', null, false));

        return true;
    }

    /**
     * Execute an SQL statement and return the number of affected rows.
     *
     * @param string $query The SQL query to prepare and execute.
     * @param array|null $params The parameters to bind.
     *
     * @return int The number of rows that were modified or deleted by the SQL statement.
     */
    public function exec(string $query, ?array $params = null): int
    {
        $libsqlStatement = new Statement($query, $params, false);
        $statementResult = $this->stream->executeStatement($libsqlStatement);

        return $statementResult->affectedRowsCount;
    }

    /**
     * Checks if inside a transaction.
     *
     * @return bool Returns true if a transaction is currently active, and false if not.
     */
    public function inTransaction(): bool
    {
        $streamId = $this->stream->streamId;
        $request = new GetAutoCommitRequest($streamId);

        /** @var \Rexpl\Libsql\Hrana\Response\GetAutoCommitResponse $response */
        $response = $this->stream->request($request);

        return ! $response->isAutoCommit;
    }

    /**
     * Returns the ID of the last inserted row.
     *
     * @return string|null String representation of the row ID of the last row that was inserted.
     */
    public function lastInsertId(): ?string
    {
        return $this->stream->lastInsertedRowId;
    }

    /**
     * Prepares an SQL statement to be executed by {@see \Rexpl\Libsql\LibsqlStatement::execute()}.
     *
     * @param string $query The SQL statement to prepare.
     *
     * @return \Rexpl\Libsql\LibsqlStatement Returns a statement object.
     */
    public function prepare(string $query): LibsqlStatement
    {
        return new LibsqlStatement(
            $query,
            $this->stream,
            $this->defaultFetchMode,
            $this->fetchClassOrCallable,
            $this->fetchConstructorArguments
        );
    }

    /**
     * @param string $query The SQL statement to prepare and execute.
     * @param array|null $params The parameters to bind.
     *
     * @return \Rexpl\Libsql\LibsqlResults
     */
    public function query(string $query, ?array $params = null): LibsqlResults
    {
        $libsqlStatement = new Statement($query, $params, true);
        $statementResult = $this->stream->executeStatement($libsqlStatement);

        return new LibsqlResults(
            $statementResult,
            $this->defaultFetchMode,
            $this->fetchClassOrCallable,
            $this->fetchConstructorArguments
        );
    }

    /**
     * Rolls back a transaction.
     *
     * @return bool
     */
    public function rollBack(): bool
    {
        $this->stream->executeStatement(new Statement('ROLLBACK', null, false));

        return true;
    }

    /**
     * Tells whether the connection is still open.
     *
     * @return bool
     */
    public function isConnected(): bool
    {
        return $this->stream->isConnected();
    }

    /**
     * Returns the negotiated protocol version.
     *
     * @return \Rexpl\Libsql\Hrana\Version
     */
    public function getProtocolVersion(): Version
    {
        return $this->stream->protocolVersion;
    }

    /**
     * Opens a new stream on the current connection. A single connection can host an arbitrary number of streams.
     *
     * @see https://github.com/tursodatabase/libsql/blob/main/docs/HRANA_3_SPEC.md#overview-1
     *
     * @return static
     */
    public function newStream(): static
    {
        $newStream = $this->stream->makeNewStream();

        $instance = new static(stream: $newStream);

        $instance->setDefaultFetchMode(
            $this->defaultFetchMode,
            $this->fetchClassOrCallable,
            $this->fetchConstructorArguments
        );

        return $instance;
    }
}
