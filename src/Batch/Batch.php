<?php

declare(strict_types=1);

namespace Rexpl\Libsql\Batch;

use Rexpl\Libsql\Hrana\Batch as HranaBatch;
use Rexpl\Libsql\Hrana\BatchStep;
use Rexpl\Libsql\Hrana\Request\BatchRequest;
use Rexpl\Libsql\Hrana\Statement;
use Rexpl\Libsql\LibsqlStream;

class Batch
{
    protected int $index = 0;

    /**
     * @var \SplObjectStorage<\Rexpl\Libsql\Batch\Step,\Rexpl\Libsql\Batch\Condition>
     */
    protected \SplObjectStorage $steps;

    /**
     * @param \Rexpl\Libsql\LibsqlStream $stream
     * @param int $defaultFetchMode
     * @param mixed $classOrCallable
     * @param array $constructorArgs
     */
    public function __construct(
        protected LibsqlStream $stream,
        protected int $defaultFetchMode,
        protected mixed $classOrCallable,
        protected array $constructorArgs
    ) {
        $this->steps = new \SplObjectStorage();
    }

    /**
     * Sets the fetch mode for the batch results.
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
     * @param string $query
     * @param array|null $bindings
     *
     * @return \Rexpl\Libsql\Batch\Step
     */
    public function addStep(string $query, ?array $bindings = null): Step
    {
        $step = new Step($this->index++, new Statement($query, $bindings, true));
        $this->steps->attach($step, null);

        return $step;
    }

    /**
     * @param \Rexpl\Libsql\Batch\Condition $condition
     * @param string $query
     * @param array|null $bindings
     *
     * @return \Rexpl\Libsql\Batch\Step
     */
    public function addConditionalStep(Condition $condition, string $query, ?array $bindings = null): Step
    {
        $step = new Step($this->index++, new Statement($query, $bindings, true));
        $this->steps->attach($step, $condition);

        return $step;
    }

    /**
     * @return \Rexpl\Libsql\Batch\Results
     */
    public function execute(): Results
    {
        $steps = [];

        foreach ($this->steps as $step) {
            $condition = $this->steps->offsetGet($step);
            $steps[] = new BatchStep(
                $condition?->condition,
                $step->statement
            );
        }

        $batch = new HranaBatch($steps);
        $streamId = $this->stream->streamId;

        /** @var \Rexpl\Libsql\Hrana\Response\BatchResponse $response */
        $response = $this->stream->request(new BatchRequest($batch, $streamId));

        return new Results($response->results, $this->defaultFetchMode, $this->classOrCallable, $this->constructorArgs);
    }
}