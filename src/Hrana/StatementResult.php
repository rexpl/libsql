<?php

declare(strict_types=1);

namespace Rexpl\Libsql\Hrana;

class StatementResult
{
    /**
     * @param array<\Rexpl\Libsql\Hrana\Column> $columns
     * @param array<array<\Rexpl\Libsql\Hrana\Value>> $rows
     * @param int $affectedRowsCount
     * @param string|null $lastInsertedRowId
     */
    public function __construct(
        public array $columns,
        public array $rows,
        public int $affectedRowsCount,
        public ?string $lastInsertedRowId
    ) {}

    /**
     * @param \Rexpl\Libsql\Hrana\Version $version
     * @param \stdClass $statementResult
     *
     * @return static
     */
    public static function parseStatementResult(Version $version, \stdClass $statementResult): static
    {
        $columns = [];

        foreach ($statementResult->cols as $column) {
            $columns[] = new Column($column->name, $column->decltype);
        }

        $rows = [];

        foreach ($statementResult->rows as $row) {
            $rows[] = \array_map(fn ($value) => Value::parseForResponse($value), $row);
        }

        return new static(
            $columns, $rows, $statementResult->affected_row_count, $statementResult->last_insert_rowid
        );
    }
}