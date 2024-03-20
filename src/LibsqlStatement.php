<?php

declare(strict_types=1);

namespace Rexpl\Libsql;

use Rexpl\Libsql\Exception\LibsqlException;
use Rexpl\Libsql\Hrana\Statement;
use Rexpl\Libsql\Hrana\Value;

class LibsqlStatement
{
    /**
     * @var array<string|int,\Rexpl\Libsql\Hrana\Value>
     */
    protected array $bindings = [];

    /**
     * @param string $query
     * @param \Rexpl\Libsql\LibsqlStream $stream
     * @param int $defaultFetchMode
     * @param mixed $classOrCallable
     * @param array $constructorArgs
     */
    public function __construct(
        protected string $query,
        protected LibsqlStream $stream,
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
     * Binds a value to a parameter.
     *
     * @param string|int $param Parameter identifier. For a prepared statement using named placeholders, this will be the parameter name.
     * @param mixed $value The value to bind to the parameter.
     * @param int $type Explicit data type for the parameter, see {@see \Rexpl\Libsql\Libsql::PARAM_}* constants.
     *
     * @return $this
     */
    public function bindValue(string|int $param, mixed $value, int $type): static
    {
        $this->bindings[$param] = match ($type) {
            Libsql::PARAM_NULL => new Value('null', null),
            Libsql::PARAM_INT => new Value('integer', (string) $value),
            Libsql::PARAM_STR => new Value('text', $value),
            Libsql::PARAM_BOOL => new Value('integer', $value ? '1' : '0'),
            Libsql::PARAM_FLOAT => new Value('float', $value),
            Libsql::PARAM_BLOB => new Value('blob', \base64_encode($value)),
            default => throw new LibsqlException(\sprintf('Unknown binding type %d.', $type)),
        };

        return $this;
    }

    /**
     * @param array|null $bindings
     *
     * @return \Rexpl\Libsql\LibsqlResults
     */
    public function execute(?array $bindings = null): LibsqlResults
    {
        $statement = new Statement($this->query, $bindings + $this->bindings, true);
        $result = $this->stream->executeStatement($statement);

        return new LibsqlResults($result, $this->defaultFetchMode, $this->classOrCallable, $this->constructorArgs);
    }
}