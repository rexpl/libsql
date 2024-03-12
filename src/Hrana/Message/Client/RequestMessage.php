<?php

declare(strict_types=1);

namespace Rexpl\Libsql\Hrana\Message\Client;

use Rexpl\Libsql\Contracts\ClientMessage;
use Rexpl\Libsql\Contracts\Request;
use Rexpl\Libsql\Hrana\Version;

class RequestMessage implements ClientMessage
{
    /**
     * @param \Rexpl\Libsql\Contracts\Request $request
     * @param int $requestId
     */
    public function __construct(public Request $request, public int $requestId) {}

    /**
     * @param \Rexpl\Libsql\Hrana\Version $version
     *
     * @return array
     */
    public function getMessage(Version $version): array
    {
        return [
            'type' => 'request',
            'request_id' => $this->requestId,
            'request' => $this->request->getRequestForMessage($version),
        ];
    }
}