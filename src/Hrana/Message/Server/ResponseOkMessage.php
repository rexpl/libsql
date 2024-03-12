<?php

declare(strict_types=1);

namespace Rexpl\Libsql\Hrana\Message\Server;

use Rexpl\Libsql\Contracts\Response;
use Rexpl\Libsql\Contracts\ServerMessage;
use Rexpl\Libsql\Hrana\Response\CloseStreamResponse;
use Rexpl\Libsql\Hrana\Response\ExecuteResponse;
use Rexpl\Libsql\Hrana\Response\GetAutoCommitResponse;
use Rexpl\Libsql\Hrana\Response\OpenStreamResponse;
use Rexpl\Libsql\Hrana\Version;

class ResponseOkMessage implements ServerMessage
{
    /**
     * @param \Rexpl\Libsql\Contracts\Response $response
     */
    public function __construct(public Response $response) {}

    /**
     * @param \Rexpl\Libsql\Hrana\Version $version
     * @param \stdClass $message
     *
     * @return static
     */
    public static function parseMessage(Version $version, \stdClass $message): static
    {
        $libsqlResponse = $message->response;

        $response = match ($libsqlResponse->type) {
            'open_stream' => OpenStreamResponse::parseResponse($version, $libsqlResponse),
            'close_stream' => CloseStreamResponse::parseResponse($version, $libsqlResponse),
            'execute' => ExecuteResponse::parseResponse($version, $libsqlResponse),
            'get_autocommit' => GetAutoCommitResponse::parseResponse($version, $libsqlResponse),
        };

        return new static($response);
    }
}