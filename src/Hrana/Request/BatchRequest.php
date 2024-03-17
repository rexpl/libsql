<?php

declare(strict_types=1);

namespace Rexpl\Libsql\Hrana\Request;

use Rexpl\Libsql\Contracts\Request;
use Rexpl\Libsql\Hrana\Batch;
use Rexpl\Libsql\Hrana\BatchStep;
use Rexpl\Libsql\Hrana\Version;

readonly class BatchRequest implements Request
{
    /**
     * @param \Rexpl\Libsql\Hrana\Batch $batch
     * @param int $streamId
     */
    public function __construct(public Batch $batch, public int $streamId) {}

    /**
     * @inheritDoc
     */
    public function getRequestForMessage(Version $version): array
    {
        $steps = \array_map(
            fn (BatchStep $step): array => [
                'condition' => $step->condition?->getConditionForRequest(),
                'stmt' => $step->statement->getStatementForRequest($version),
            ],
            $this->batch->steps
        );

        return [
            'type' => 'batch',
            'stream_id' => $this->streamId,
            'batch' => [
                'steps' => $steps,
            ],
        ];
    }
}