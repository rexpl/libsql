<?php

declare(strict_types=1);

namespace Rexpl\Libsql\Batch;

use Rexpl\Libsql\Hrana\BatchCondition;

readonly class Condition
{
    /**
     * @param \Rexpl\Libsql\Hrana\BatchCondition $condition
     */
    protected function __construct(
        public BatchCondition $condition,
    ) {}

    /**
     * Evaluates to true if the step was executed successfully. If the statement was skipped, this condition evaluates to false.
     *
     * @param \Rexpl\Libsql\Batch\Step $step
     *
     * @return static
     */
    public static function ok(Step $step): static
    {
        return new Condition(new BatchCondition('ok', [$step->index]));
    }

    /**
     * Evaluates to true if the step has produced an error. If the statement was skipped, this condition evaluates to false.
     *
     * @param \Rexpl\Libsql\Batch\Step $step
     *
     * @return static
     */

    public static function error(Step $step): static
    {
        return new Condition(new BatchCondition('error', [$step->index]));
    }

    /**
     * Evaluates the condition and returns the logical negative.
     *
     * @param \Rexpl\Libsql\Batch\Condition $condition
     *
     * @return static
     */
    public static function not(Condition $condition): static
    {
        return new Condition(new BatchCondition('not', [$condition->condition]));
    }

    /**
     * Evaluates the conditions and returns the logical conjunction of them.
     *
     * @param \Rexpl\Libsql\Batch\Condition ...$conditions
     *
     * @return static
     */
    public static function and(Condition ...$conditions): static
    {
        $hranaConditions = \array_map(fn ($c) => $c->condition, $conditions);

        return new Condition(new BatchCondition('and', $hranaConditions));
    }

    /**
     * Evaluates the conditions and returns the logical disjunction of them.
     *
     * @param \Rexpl\Libsql\Batch\Condition ...$conditions
     *
     * @return static
     */
    public static function or(Condition ...$conditions): static
    {
        $hranaConditions = \array_map(fn ($c) => $c->condition, $conditions);

        return new Condition(new BatchCondition('or', $hranaConditions));
    }

    /**
     * Evaluates to true if the stream is currently in the autocommit state (not inside an explicit transaction).
     *
     * @return static
     */
    public static function isAutocommit(): static
    {
        return new Condition(new BatchCondition('is_autocommit'));
    }
}