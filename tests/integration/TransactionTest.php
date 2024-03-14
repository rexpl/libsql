<?php

declare(strict_types=1);

namespace Rexpl\Libsql\Tests\integration;

use PHPUnit\Framework\TestCase;
use Rexpl\Libsql\Tests\test_driver\TestDriver;

class TransactionTest extends TestCase
{
    public function test_not_in_transaction(): void
    {
        $libsql = TestDriver::make();

        $this->assertFalse($libsql->inTransaction());
    }
}