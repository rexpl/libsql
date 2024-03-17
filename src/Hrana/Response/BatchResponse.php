<?php

declare(strict_types=1);

namespace Rexpl\Libsql\Hrana\Response;

use Rexpl\Libsql\Contracts\Response;
use Rexpl\Libsql\Hrana\BatchResults;
use Rexpl\Libsql\Hrana\Version;

readonly class BatchResponse implements Response
{
    /**
     * @param \Rexpl\Libsql\Hrana\BatchResults $results
     */
    public function __construct(public BatchResults $results) {}

    /**
     * @inheritDoc
     */
    public static function parseResponse(Version $version, \stdClass $response): static
    {
        $batchResults = BatchResults::parseBatchResults($version, $response->result);

        return new static($batchResults);
    }
}