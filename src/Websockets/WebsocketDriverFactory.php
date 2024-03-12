<?php

declare(strict_types=1);

namespace Rexpl\Libsql\Websockets;

use Rexpl\Libsql\Contracts\WebsocketDriver;

class WebsocketDriverFactory
{
    /**
     * @return \Rexpl\Libsql\Contracts\WebsocketDriver
     */
    public static function create(): WebsocketDriver
    {
        return new DefaultDriver();
    }
}