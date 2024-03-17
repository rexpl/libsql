<?php

declare(strict_types=1);

namespace Rexpl\Libsql\Batch;

use Rexpl\Libsql\Hrana\Statement;

readonly class Step
{
    /**
     * @param int $index
     * @param \Rexpl\Libsql\Hrana\Statement $statement
     */
    public function __construct(public int $index, public Statement $statement) {}
}