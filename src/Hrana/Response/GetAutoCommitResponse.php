<?php

declare(strict_types=1);

namespace Rexpl\Libsql\Hrana\Response;

use Rexpl\Libsql\Contracts\Response;
use Rexpl\Libsql\Hrana\Version;

class GetAutoCommitResponse implements Response
{
    /**
     * @param bool $isAutoCommit
     */
    public function __construct(public bool $isAutoCommit) {}

    /**
     * @param \Rexpl\Libsql\Hrana\Version $version
     * @param \stdClass $response
     *
     * @return static
     */
    public static function parseResponse(Version $version, \stdClass $response): static
    {
        return new static($response->is_autocommit);
    }
}