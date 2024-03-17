<?php

declare(strict_types=1);

namespace Rexpl\Libsql\Batch;

use Rexpl\Libsql\Exception\LibsqlException;
use Rexpl\Libsql\Hrana\BatchResults;
use Rexpl\Libsql\LibsqlResults;

class Results
{
    /**
     * @param \Rexpl\Libsql\Hrana\BatchResults $results
     * @param int $defaultFetchMode
     * @param mixed $classOrCallable
     * @param array $constructorArgs
     */
    public function __construct(
        protected BatchResults $results,
        protected int $defaultFetchMode,
        protected mixed $classOrCallable,
        protected array $constructorArgs
    ) {}

    /**
     * @param \Rexpl\Libsql\Batch\Step $step
     * @param bool $throw
     *
     * @return \Rexpl\Libsql\LibsqlResults|null
     */
    public function getResultForStep(Step $step, bool $throw = true): ?LibsqlResults
    {
        $index = $step->index;

        if ($throw && $this->results->stepErrors[$index] !== null) {
            $this->results->stepErrors[$index]->throw(LibsqlException::class);
        }

        if ($this->results->stepResults[$index] !== null) {
            return new LibsqlResults(
                $this->results->stepResults[$index],
                $this->defaultFetchMode,
                $this->classOrCallable,
                $this->constructorArgs
            );
        }

        return null;
    }
}