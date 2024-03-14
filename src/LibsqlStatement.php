<?php

declare(strict_types=1);

namespace Rexpl\Libsql;

use Rexpl\Libsql\Hrana\Statement;

class LibsqlStatement
{
    /**
     * @param string $query
     * @param \Rexpl\Libsql\LibsqlConnection $connection
     * @param int $defaultFetchMode
     * @param mixed $classOrCallable
     * @param array $constructorArgs
     */
    public function __construct(
        protected string $query,
        protected LibsqlConnection $connection,
        protected int $defaultFetchMode,
        protected mixed $classOrCallable,
        protected array $constructorArgs
    ) {}

    /**
     * Sets the fetch mode for the results of the prepare statement.
     *
     * @param int $mode The fetch mode see {@see \Rexpl\Libsql\Libsql::FETCH_}* constants.
     * @param string|callable|null $classOrCallable Name of the created class for {@see \Rexpl\Libsql\Libsql::FETCH_CLASS},
     *  or the callable for {@see \Rexpl\Libsql\Libsql::FETCH_FUNC}.
     * @param array $constructorArgs Elements of this array are passed to the constructor ({@see \Rexpl\Libsql\Libsql::FETCH_CLASS}).
     */
    public function setFetchMode(int $mode, string|callable|null $classOrCallable = null, array $constructorArgs = []): void
    {
        $this->defaultFetchMode = $mode;
        $this->classOrCallable = $classOrCallable;
        $this->constructorArgs = $constructorArgs;
    }

    /**
     * @param array|null $bindings
     *
     * @return \Rexpl\Libsql\LibsqlResults
     */
    public function execute(?array $bindings = null): LibsqlResults
    {
        $statement = new Statement($this->query, $bindings, true);
        $result = $this->connection->executeStatement($statement);

        return new LibsqlResults($result, $this->defaultFetchMode, $this->classOrCallable, $this->constructorArgs);
    }
}