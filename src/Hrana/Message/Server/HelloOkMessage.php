<?php

declare(strict_types=1);

namespace Rexpl\Libsql\Hrana\Message\Server;

use Rexpl\Libsql\Contracts\ServerMessage;
use Rexpl\Libsql\Hrana\Version;

class HelloOkMessage implements ServerMessage
{
    /**
     * @param \Rexpl\Libsql\Hrana\Version $version
     * @param \stdClass $message
     *
     * @return static
     */
    public static function parseMessage(Version $version, \stdClass $message): static
    {
        return new static();
    }
}