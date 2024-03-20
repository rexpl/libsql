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
     * @var int
     */
    protected int $iterationIndex = 0;

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
        $index = $this->currentIndex++;

        if (!isset($this->result->rows[$index])) {
            return null;
        }

        $row = $this->result->rows[$index];

        return match ($mode ?? $this->defaultFetchMode) {
            Libsql::FETCH_ASSOC => $this->fetchAssoc($row),
            Libsql::FETCH_NUM => $this->fetchNum($row),
            Libsql::FETCH_OBJ => $this->fetchObj($row),
            Libsql::FETCH_CLASS => $this->fetchClass(
                $row,
                $classOrCallable ?? $this->classOrCallable,
                empty($this->constructorArgs) ? $this->constructorArgs : $constructorArgs
            ),
            Libsql::FETCH_FUNC => $this->fetchFunction($row, $classOrCallable ?? $this->classOrCallable),
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
     * @param array<\Rexpl\Libsql\Hrana\Value> $row
     *
     * @return array<string,mixed>
     */
    protected function fetchAssoc(array $row): array
    {
        $returnRow = [];

        foreach ($this->result->columns as $key => $column) {
            $returnRow[$column->name] = $row[$key]->value;
        }

        return $returnRow;
    }

    /**
     * @return array<array<string,mixed>>
     */
    protected function fetchAllAssoc(): array
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
     * @param array<\Rexpl\Libsql\Hrana\Value> $row
     *
     * @return array
     */
    protected function fetchNum(array $row): array
    {
        return \array_map(fn (Value $value): mixed => $value->value, $row);
    }

    /**
     * @return array<array>
     */
    protected function fetchAllNum(): array
    {
        $result = [];

        foreach ($this->result->rows as $row) {
            $result[] = \array_map(fn (Value $value): mixed => $value->value, $row);
        }

        return $result;
    }

    /**
     * @param array<\Rexpl\Libsql\Hrana\Value> $row
     *
     * @return \stdClass
     */
    protected function fetchObj(array $row): \stdClass
    {
        return $this->fetchClass($row, \stdClass::class);
    }

    /**
     * @return array<\stdClass>
     */
    protected function fetchAllObj(): array
    {
        return $this->fetchAllClass(\stdClass::class);
    }

    /**
     * @param array<\Rexpl\Libsql\Hrana\Value> $row
     * @param string $class
     * @param array $constructorArgs
     *
     * @return object
     */
    protected function fetchClass(array $row, string $class, array $constructorArgs = []): object
    {
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
    protected function fetchAllClass(string $class, array $constructorArgs = []): array
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
     * @param array<\Rexpl\Libsql\Hrana\Value> $row
     * @param callable $callable
     *
     * @return mixed
     */
    protected function fetchFunction(array $row, callable $callable): mixed
    {
        $columns = \array_map(fn (Column $column): string => $column->name, $this->result->columns);
        $rows = \array_map(fn (Value $value): mixed => $value->value, $row);

        return $callable($columns, $rows);
    }

    /**
     * @param callable $callable
     *
     * @return array
     */
    protected function fetchAllFunction(callable $callable): array
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
        $row = $this->result->rows[$this->iterationIndex];

        return match ($this->defaultFetchMode) {
            Libsql::FETCH_ASSOC => $this->fetchAssoc($row),
            Libsql::FETCH_NUM => $this->fetchNum($row),
            Libsql::FETCH_OBJ => $this->fetchObj($row),
            Libsql::FETCH_CLASS => $this->fetchClass($row, $this->classOrCallable, $this->constructorArgs),
            Libsql::FETCH_FUNC => $this->fetchFunction($row, $this->classOrCallable),
            default => throw new LibsqlException('Unknown fetch mode.'),
        };
    }

    public function next(): void
    {
        $this->iterationIndex++;
    }

    public function key(): mixed
    {
        return $this->iterationIndex;
    }

    public function valid(): bool
    {
        return isset($this->result->rows[$this->iterationIndex]);
    }

    public function rewind(): void
    {
        $this->iterationIndex = 0;
    }
}