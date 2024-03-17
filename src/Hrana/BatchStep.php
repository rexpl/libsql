<?php

declare(strict_types=1);

namespace Rexpl\Libsql\Hrana;

readonly class BatchStep
{
    /**
     * @param \Rexpl\Libsql\Hrana\BatchCondition|null $condition
     * @param \Rexpl\Libsql\Hrana\Statement $statement
     */
    public function __construct(public ?BatchCondition $condition, public Statement $statement) {}
}