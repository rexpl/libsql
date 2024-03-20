<?php

declare(strict_types=1);

namespace Rexpl\Libsql\Tests;

use PHPUnit\Framework\TestCase;
use Rexpl\Libsql\Hrana\Statement;
use Rexpl\Libsql\Hrana\Value;
use Rexpl\Libsql\Hrana\Version;

class StatementTest extends TestCase
{
    public function test_statement_basics(): void
    {
        $statement = new Statement('query', null, true);
        $request = $statement->getStatementForRequest(Version::HRANA_3);

        $this->assertArrayIsIdenticalToArrayIgnoringListOfKeys(
            [
                'sql' => 'query',
                'want_rows' => true,
            ],
            $request,
            []
        );
    }

    public function test_statement_named_arguments(): void
    {
        $statement = new Statement('query', ['named' => 'arg'], true);
        $request = $statement->getStatementForRequest(Version::HRANA_3);

        $this->assertArrayIsIdenticalToArrayIgnoringListOfKeys(
            [
                'named_args' => [
                    [
                        'name' => 'named',
                        'value' => [
                            'type' => 'text',
                            'value' => 'arg',
                        ],
                    ],
                ],
            ],
            $request,
            ['sql', 'want_rows']
        );
    }

    public function test_statement_arguments(): void
    {
        $statement = new Statement('query', ['arg'], true);
        $request = $statement->getStatementForRequest(Version::HRANA_3);

        $this->assertArrayIsIdenticalToArrayIgnoringListOfKeys(
            [
                'args' => [
                    [
                        'type' => 'text',
                        'value' => 'arg',
                    ],
                ],
            ],
            $request,
            ['sql', 'want_rows']
        );
    }

    public function test_statement_with_bind_value(): void
    {
        $statement = new Statement(
            'query',
            [
                'test' => new Value('text', 'text'),
                new Value('text', 'test'),
            ],
            true
        );
        $request = $statement->getStatementForRequest(Version::HRANA_3);

        $this->assertArrayIsIdenticalToArrayIgnoringListOfKeys(
            [
                'named_args' => [
                    [
                        'name' => 'test',
                        'value' => [
                            'type' => 'text',
                            'value' => 'text',
                        ],
                    ],
                ],
                'args' => [
                    [
                        'type' => 'text',
                        'value' => 'test',
                    ],
                ],
            ],
            $request,
            ['sql', 'want_rows']
        );
    }
}