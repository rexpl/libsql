<?php

declare(strict_types=1);

namespace Rexpl\Libsql\Hrana\Response;

use Rexpl\Libsql\Contracts\Response;
use Rexpl\Libsql\Hrana\StatementResult;
use Rexpl\Libsql\Hrana\Version;

class ExecuteResponse implements Response
{
    /**
     * @param \Rexpl\Libsql\Hrana\StatementResult $statementResult
     */
    public function __construct(public StatementResult $statementResult) {}

    /**
     * @param \Rexpl\Libsql\Hrana\Version $version
     * @param \stdClass $response
     *
     * @return static
     */
    public static function parseResponse(Version $version, \stdClass $response): static
    {
        $statementResult = StatementResult::parseStatementResult($version, $response->result);

        return new static($statementResult);
    }
}