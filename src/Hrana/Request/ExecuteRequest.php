<?php

declare(strict_types=1);

namespace Rexpl\Libsql\Hrana\Request;

use Rexpl\Libsql\Contracts\Request;
use Rexpl\Libsql\Hrana\Statement;
use Rexpl\Libsql\Hrana\Version;

class ExecuteRequest implements Request
{
    /**
     * @param int $streamId
     * @param \Rexpl\Libsql\Hrana\Statement $statement
     */
    public function __construct(public int $streamId, public Statement $statement) {}

    /**
     * @param \Rexpl\Libsql\Hrana\Version $version
     *
     * @return array
     */
    public function getRequestForMessage(Version $version): array
    {
        return [
            'type' => 'execute',
            'stream_id' => $this->streamId,
            'stmt' => $this->statement->getStatementForRequest($version),
        ];
    }
}