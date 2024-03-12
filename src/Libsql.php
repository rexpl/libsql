<?php

declare(strict_types=1);

namespace Rexpl\Libsql;

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
    public const VERSION = '0.1.0';

    public const FETCH_ASSOC = 2;
    public const FETCH_NUM = 3;
    public const FETCH_OBJ = 5;
    public const FETCH_CLASS = 8;
    public const FETCH_FUNC = 10;

    /**
     * @var \Rexpl\Libsql\Hrana\Version
     */
    public readonly Version $protocolVersion;

    /**
     * @var \Rexpl\Libsql\LibsqlConnection
     */
    protected LibsqlConnection $connection;

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
     *
     * @throws \Rexpl\Libsql\Exception\ConnectionException
     */
    public function __construct(string $url, #[\SensitiveParameter] ?string $token, bool $secure = true)
    {
        $token = new \SensitiveParameterValue($token);
        $this->connection = new LibsqlConnection();

        $this->protocolVersion = $this->connection->connect($url, $token, $secure, static::VERSION);
    }

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
        $this->connection->executeStatement(new Statement('BEGIN TRANSACTION', null, false));

        return true;
    }

    /**
     * Commits a transaction.
     *
     * @return bool
     */
    public function commit(): bool
    {
        $this->connection->executeStatement(new Statement('COMMIT', null, false));

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
        $statementResult = $this->connection->executeStatement($libsqlStatement);

        return $statementResult->affectedRowsCount;
    }

    /**
     * Checks if inside a transaction.
     *
     * @return bool Returns true if a transaction is currently active, and false if not.
     */
    public function inTransaction(): bool
    {
        $streamId = $this->connection->getStreamId();
        $request = new GetAutoCommitRequest($streamId);

        /** @var \Rexpl\Libsql\Hrana\Response\GetAutoCommitResponse $response */
        $response = $this->connection->request($request);

        return ! $response->isAutoCommit;
    }

    /**
     * Returns the ID of the last inserted row.
     *
     * @return string|null String representation of the row ID of the last row that was inserted.
     */
    public function lastInsertId(): ?string
    {
        return $this->connection->lastInsertedRowId;
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
            $this->connection,
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
        $libsqlStatement = new Statement($query, $params, false);
        $statementResult = $this->connection->executeStatement($libsqlStatement);

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
        $this->connection->executeStatement(new Statement('ROLLBACK', null, false));

        return true;
    }

    /**
     * Tells whether the client is still connected.
     *
     * @return bool
     */
    public function isConnected(): bool
    {
        return $this->connection->isConnected();
    }
}