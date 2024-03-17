<?php

declare(strict_types=1);

namespace Rexpl\Libsql\Hrana;

readonly class Batch
{
    /**
     * @param array<\Rexpl\Libsql\Hrana\BatchStep> $steps
     */
    public function __construct(public array $steps) {}
}