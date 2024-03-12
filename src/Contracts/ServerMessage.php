<?php

declare(strict_types=1);

namespace Rexpl\Libsql\Contracts;

use Rexpl\Libsql\Hrana\Version;

interface ServerMessage
{
    /**
     * @param \Rexpl\Libsql\Hrana\Version $version
     * @param \stdClass $message
     *
     * @return static
     */
    public static function parseMessage(Version $version, \stdClass $message): static;
}