<?php

declare(strict_types=1);

namespace Rexpl\Libsql\Tests\integration;

use PHPUnit\Framework\TestCase;
use Rexpl\Libsql\Libsql;
use Rexpl\Libsql\Tests\test_driver\TestDriver;

class ResultsTest extends TestCase
{
    protected function get_query(): string
    {
        $query = new \stdClass();
        $query->rowCount = 10;
        $query->cols = ['name', 'age'];

        return \serialize($query);
    }

    public function test_fetch_assoc(): void
    {
        $libsql = TestDriver::make();

        $results = $libsql->query($this->get_query());

        $firstRow = $results->fetch(Libsql::FETCH_ASSOC);

        $this->assertIsArray($firstRow);

        $this->assertArrayHasKey('name', $firstRow);
        $this->assertArrayHasKey('age', $firstRow);
    }

    public function test_fetch_assoc_all(): void
    {
        $libsql = TestDriver::make();

        $results = $libsql->query($this->get_query());

        $rows = $results->fetchAll(Libsql::FETCH_ASSOC);

        $this->assertCount(10, $rows);

        $firstRow = $rows[0];

        $this->assertIsArray($firstRow);

        $this->assertArrayHasKey('name', $firstRow);
        $this->assertArrayHasKey('age', $firstRow);
    }

    public function test_fetch_object(): void
    {
        $libsql = TestDriver::make();

        $results = $libsql->query($this->get_query());

        $firstRow = $results->fetch(Libsql::FETCH_OBJ);

        $this->assertTrue($firstRow instanceof \stdClass);

        $this->assertObjectHasProperty('name', $firstRow);
        $this->assertObjectHasProperty('age', $firstRow);
    }

    public function test_fetch_object_all(): void
    {
        $libsql = TestDriver::make();

        $results = $libsql->query($this->get_query());

        $rows = $results->fetchAll(Libsql::FETCH_OBJ);

        $this->assertCount(10, $rows);

        $firstRow = $rows[0];

        $this->assertTrue($firstRow instanceof \stdClass);

        $this->assertObjectHasProperty('name', $firstRow);
        $this->assertObjectHasProperty('age', $firstRow);
    }

    public function test_fetch_numeric(): void
    {
        $libsql = TestDriver::make();

        $results = $libsql->query($this->get_query());

        $firstRow = $results->fetch(Libsql::FETCH_NUM);

        $this->assertIsArray($firstRow);

        // Seems pointless...
        $this->assertArrayHasKey(0, $firstRow);
        $this->assertArrayHasKey(1, $firstRow);
    }

    public function test_fetch_numeric_all(): void
    {
        $libsql = TestDriver::make();

        $results = $libsql->query($this->get_query());

        $rows = $results->fetchAll(Libsql::FETCH_NUM);

        $this->assertCount(10, $rows);

        $firstRow = $rows[0];

        $this->assertIsArray($firstRow);

        // Seems pointless...
        $this->assertArrayHasKey(0, $firstRow);
        $this->assertArrayHasKey(1, $firstRow);
    }

    public function test_fetch_function(): void
    {
        $libsql = TestDriver::make();

        $results = $libsql->query($this->get_query());

        $firstRow = $results->fetch(
            Libsql::FETCH_FUNC,
            function ($cols, $values) {
                foreach ($values as $key => $value) {
                    $result[$cols[$key]] = $value;
                }
                return $result;
            }
        );

        $this->assertTrue(\is_array($firstRow) && isset($firstRow['name']) && isset($firstRow['age']));
    }

    public function test_iterable_results(): void
    {
        $libsql = TestDriver::make();

        $results = $libsql->query($this->get_query());

        $i = 0;

        foreach ($results as $key => $row) {
            $i++;
        }

        $this->assertEquals($results->rowCount(), $i);
    }

    public function test_column_count(): void
    {
        $libsql = TestDriver::make();

        $results = $libsql->query($this->get_query());

        $this->assertEquals(2, $results->columnCount());
    }

    public function test_default_fetch_mode(): void
    {
        $libsql = TestDriver::make();
        $libsql->setDefaultFetchMode(Libsql::FETCH_OBJ);

        $results = $libsql->query($this->get_query());

        $firstRow = $results->fetch();

        $this->assertTrue($firstRow instanceof \stdClass);

        $this->assertObjectHasProperty('name', $firstRow);
        $this->assertObjectHasProperty('age', $firstRow);
    }

    public function test_get_one_row(): void
    {
        $libsql = TestDriver::make();

        $query = new \stdClass();
        $query->rowCount = 1;
        $query->cols = ['name'];

        $results = $libsql->query(\serialize($query));
        $firstRow = $results->fetch();

        $this->assertArrayHasKey('name', $firstRow);
    }
}