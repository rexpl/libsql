<?php

declare(strict_types=1);

namespace Rexpl\Libsql\Hrana;

readonly class BatchResults
{
    /**
     * @param array<\Rexpl\Libsql\Hrana\StatementResult|null> $stepResults
     * @param array<\Rexpl\Libsql\Hrana\Error|null> $stepErrors
     */
    public function __construct(public array $stepResults, public array $stepErrors) {}

    /**
     * @param \Rexpl\Libsql\Hrana\Version $version
     * @param \stdClass $batchResult
     *
     * @return static
     */
    public static function parseBatchResults(Version $version, \stdClass $batchResult): static
    {
        $results = [];

        foreach ($batchResult->step_results as $result) {
            $results[] = $result === null ? null : StatementResult::parseStatementResult($version, $result);
        }

        $errors = [];

        foreach ($batchResult->step_errors as $error) {
            $errors[] = $error === null ? null : new Error($error->message, $error->code);
        }

        return new static($results, $errors);
    }
}