<?php

declare(strict_types=1);

namespace Rexpl\Libsql\Hrana\Message\Server;

use Rexpl\Libsql\Contracts\ServerMessage;
use Rexpl\Libsql\Hrana\Error;
use Rexpl\Libsql\Hrana\Version;

class ResponseErrorMessage implements ServerMessage
{
    /**
     * @param \Rexpl\Libsql\Hrana\Error $error
     */
    public function __construct(public Error $error) {}

    /**
     * @param \Rexpl\Libsql\Hrana\Version $version
     * @param \stdClass $message
     *
     * @return static
     */
    public static function parseMessage(Version $version, \stdClass $message): static
    {
        $libsqlError = $message->error;
        $error = new Error($libsqlError->message, $libsqlError->code);

        return new static($error);
    }
}