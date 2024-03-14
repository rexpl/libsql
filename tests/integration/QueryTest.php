<?php

declare(strict_types=1);

namespace Rexpl\Libsql\Tests\integration;

use PHPUnit\Framework\TestCase;
use Rexpl\Libsql\Exception\LibsqlException;
use Rexpl\Libsql\Tests\test_driver\TestDriver;

class QueryTest extends TestCase
{
    public function test_exec(): void
    {
        $libsql = TestDriver::make();

        $libsql->exec(\serialize(new \stdClass()));

        $this->expectException(LibsqlException::class);

        $libsql->exec('malformed query');
    }

    public function test_simple_query(): void
    {
        $libsql = TestDriver::make();

        $query = new \stdClass();
        $query->rowCount = 50;

        $results = $libsql->query(\serialize($query));

        $this->assertCount(50, $results->fetchAll());
    }

    public function test_prepared_query(): void
    {
        $libsql = TestDriver::make();

        $query = new \stdClass();
        $query->rowCount = 50;

        $stmt = $libsql->prepare(\serialize($query));
        $results = $stmt->execute();

        $this->assertCount(50, $results->fetchAll());
    }

    public function test_last_insert_id(): void
    {
        $libsql = TestDriver::make();

        $query = new \stdClass();
        $query->lastInsertId = \rand(1, 10_000);

        $libsql->exec(\serialize($query));

        $this->assertEquals($query->lastInsertId, $libsql->lastInsertId());
    }
}