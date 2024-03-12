<?php

declare(strict_types=1);

namespace Rexpl\Libsql\Contracts;

use Rexpl\Libsql\Hrana\Version;

interface Response
{
    /**
     * @param \Rexpl\Libsql\Hrana\Version $version
     * @param \stdClass $response
     *
     * @return static
     */
    public static function parseResponse(Version $version, \stdClass $response): static;
}