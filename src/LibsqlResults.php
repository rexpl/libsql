<?php

declare(strict_types=1);

namespace Rexpl\Libsql;

use Rexpl\Libsql\Exception\LibsqlException;
use Rexpl\Libsql\Hrana\Column;
use Rexpl\Libsql\Hrana\StatementResult;
use Rexpl\Libsql\Hrana\Value;

class LibsqlResults implements \Iterator
{
    /**
     * @var int
     */
    protected int $currentIndex = 0;

    /**
     * @param \Rexpl\Libsql\Hrana\StatementResult $result
     * @param int $defaultFetchMode
     * @param mixed $classOrCallable
     * @param array $constructorArgs
     */
    public function __construct(
        protected StatementResult $result,
        protected int $defaultFetchMode,
        protected mixed $classOrCallable,
        protected array $constructorArgs
    ) {}

    /**
     * Sets the fetch mode for the result set.
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
     * Fetches the next row from the result set.
     *
     * @param int|null $mode Controls how the next row will be returned to the caller.
     * @param string|callable|null $classOrCallable Name of the created class for {@see \Rexpl\Libsql\Libsql::FETCH_CLASS},
     * or the callable for {@see \Rexpl\Libsql\Libsql::FETCH_FUNC}.
     * @param array $constructorArgs Elements of this array are passed to the constructor ({@see \Rexpl\Libsql\Libsql::FETCH_CLASS}).
     *
     * @return mixed The return value of this function on success depends on the fetch type. In all cases, null is
     * returned if there are no more rows.
     */
    public function fetch(?int $mode = null, string|callable|null $classOrCallable = null, array $constructorArgs = []): mixed
    {
        return match ($mode ?? $this->defaultFetchMode) {
            Libsql::FETCH_ASSOC => $this->fetchAssoc(),
            Libsql::FETCH_NUM => $this->fetchNum(),
            Libsql::FETCH_OBJ => $this->fetchObj(),
            Libsql::FETCH_CLASS => $this->fetchClass(
                $classOrCallable ?? $this->classOrCallable,
                empty($this->constructorArgs) ? $this->constructorArgs : $constructorArgs
            ),
            Libsql::FETCH_FUNC => $this->fetchFunction($classOrCallable ?? $this->classOrCallable),
            default => throw new LibsqlException('Unknown fetch mode.'),
        };
    }

    /**
     * Fetches all the rows from the result set.
     *
     * @param int|null $mode Controls how the next row will be returned to the caller.
     * @param string|callable|null $classOrCallable Name of the created class for {@see \Rexpl\Libsql\Libsql::FETCH_CLASS},
     * or the callable for {@see \Rexpl\Libsql\Libsql::FETCH_FUNC}.
     * @param array $constructorArgs Elements of this array are passed to the constructor ({@see \Rexpl\Libsql\Libsql::FETCH_CLASS}).
     *
     * @return array Returns an array containing all the rows in the result set.
     */
    public function fetchAll(?int $mode = null, string|callable|null $classOrCallable = null, array $constructorArgs = []): array
    {
        return match ($mode ?? $this->defaultFetchMode) {
            Libsql::FETCH_ASSOC => $this->fetchAllAssoc(),
            Libsql::FETCH_NUM => $this->fetchAllNum(),
            Libsql::FETCH_OBJ => $this->fetchAllObj(),
            Libsql::FETCH_CLASS => $this->fetchAllClass(
                $classOrCallable ?? $this->classOrCallable,
                empty($this->constructorArgs) ? $this->constructorArgs : $constructorArgs
            ),
            Libsql::FETCH_FUNC => $this->fetchAllFunction($classOrCallable ?? $this->classOrCallable),
            default => throw new LibsqlException('Unknown fetch mode.'),
        };
    }

    /**
     * @return array<string,mixed>|null
     */
    public function fetchAssoc(): ?array
    {
        $row = $this->getCurrentRow();

        if ($row === null) {
            return null;
        }

        $returnRow = [];

        foreach ($this->result->columns as $key => $column) {
            $returnRow[$column->name] = $row[$key]->value;
        }

        return $returnRow;
    }

    /**
     * @return array<array<string,mixed>>
     */
    public function fetchAllAssoc(): array
    {
        $result = [];

        foreach ($this->result->rows as $row) {
            $returnRow = [];

            foreach ($this->result->columns as $key => $column) {
                $returnRow[$column->name] = $row[$key]->value;
            }

            $result[] = $returnRow;
        }

        return $result;
    }

    /**
     * @return array|null
     */
    public function fetchNum(): ?array
    {
        return null !== ($row = $this->getCurrentRow())
            ? \array_map(fn (Value $value): mixed => $value->value, $row)
            : null;
    }

    /**
     * @return array<array>
     */
    public function fetchAllNum(): array
    {
        $result = [];

        foreach ($this->result->rows as $row) {
            $result[] = \array_map(fn (Value $value): mixed => $value->value, $row);
        }

        return $result;
    }

    /**
     * @return \stdClass|null
     */
    public function fetchObj(): ?\stdClass
    {
        return $this->fetchClass(\stdClass::class);
    }

    /**
     * @return array<\stdClass>
     */
    public function fetchAllObj(): array
    {
        return $this->fetchAllClass(\stdClass::class);
    }

    /**
     * @param string $class
     * @param array $constructorArgs
     *
     * @return object|null
     */
    public function fetchClass(string $class, array $constructorArgs = []): ?object
    {
        $row = $this->getCurrentRow();

        if ($row === null) {
            return null;
        }

        $returnRow = new $class(...$constructorArgs);

        foreach ($this->result->columns as $key => $column) {
            $returnRow->{$column->name} = $row[$key]->value;
        }

        return $returnRow;
    }

    /**
     * @param string $class
     * @param array $constructorArgs
     *
     * @return array<object>
     */
    public function fetchAllClass(string $class, array $constructorArgs = []): array
    {
        $result = [];

        foreach ($this->result->rows as $row) {
            $returnRow = new $class(...$constructorArgs);

            foreach ($this->result->columns as $key => $column) {
                $returnRow->{$column->name} = $row[$key]->value;
            }

            $result[] = $returnRow;
        }

        return $result;
    }

    /**
     * @param callable $callable
     *
     * @return mixed
     */
    public function fetchFunction(callable $callable): mixed
    {
        $row = $this->getCurrentRow();

        if ($row === null) {
            return null;
        }

        $columns = \array_map(fn (Column $column): string => $column->name, $this->result->columns);
        $rows = \array_map(fn (Value $value): mixed => $value->value, $row);

        return $callable($columns, $rows);
    }

    /**
     * @param callable $callable
     *
     * @return array
     */
    public function fetchAllFunction(callable $callable): array
    {
        $columns = \array_map(fn (Column $column): string => $column->name, $this->result->columns);
        $result = [];

        foreach ($this->result->rows as $row) {
            $result[] = $callable(
                $columns,
                \array_map(fn (Value $value): mixed => $value->value, $row)
            );
        }

        return $result;
    }

    /**
     * @return array<\Rexpl\Libsql\Hrana\Value>|null
     */
    protected function getCurrentRow(): ?array
    {
        return $this->result->rows[++$this->currentIndex] ?? null;
    }

    /**
     * Returns the number of columns in the result set.
     *
     * @return int Returns the number of columns.
     */
    public function columnCount(): int
    {
        return count($this->result->columns);
    }

    /**
     * Returns the number of rows in the result set.
     *
     * @return int Returns the number of rows.
     */
    public function rowCount(): int
    {
        return \count($this->result->rows);
    }

    /**
     * Returns the number of rows affected by the last SQL statement.
     *
     * @return int Returns the number of rows.
     */
    public function affectedRowCount(): int
    {
        return $this->result->affectedRowsCount;
    }

    public function current(): mixed
    {
        $this->currentIndex--;
        return $this->fetch($this->defaultFetchMode, $this->classOrCallable, $this->constructorArgs);
    }

    public function next(): void
    {
        $this->currentIndex++;
    }

    public function key(): mixed
    {
        return $this->currentIndex;
    }

    public function valid(): bool
    {
        return isset($this->result->rows[$this->currentIndex]);
    }

    public function rewind(): void
    {
        $this->currentIndex = 0;
    }
}