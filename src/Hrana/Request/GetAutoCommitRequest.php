<?php

declare(strict_types=1);

namespace Rexpl\Libsql\Hrana\Request;

use Rexpl\Libsql\Contracts\Request;
use Rexpl\Libsql\Hrana\Version;

class GetAutoCommitRequest implements Request
{
    /**
     * @param int $streamId
     */
    public function __construct(public int $streamId) {}

    /**
     * @param \Rexpl\Libsql\Hrana\Version $version
     *
     * @return array
     */
    public function getRequestForMessage(Version $version): array
    {
        return [
            'type' => 'get_autocommit',
            'stream_id' => $this->streamId,
        ];
    }
}