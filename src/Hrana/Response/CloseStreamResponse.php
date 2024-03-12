<?php

declare(strict_types=1);

namespace Rexpl\Libsql\Hrana\Response;

use Rexpl\Libsql\Contracts\Response;
use Rexpl\Libsql\Hrana\Version;

class CloseStreamResponse implements Response
{
    /**
     * @param \Rexpl\Libsql\Hrana\Version $version
     * @param \stdClass $response
     *
     * @return static
     */
    public static function parseResponse(Version $version, \stdClass $response): static
    {
        return new static();
    }
}