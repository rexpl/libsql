<?php

declare(strict_types=1);

namespace Rexpl\Libsql\Tests\integration;

use PHPUnit\Framework\TestCase;
use Rexpl\Libsql\Exception\ConnectionException;
use Rexpl\Libsql\Tests\test_driver\TestDriver;

class AuthTest extends TestCase
{
    public const VALID_AUTH_TOKEN = 'valid_auth_token';

    public function test_authentication_with_valid_auth(): void
    {
        $libsql = TestDriver::make(token: self::VALID_AUTH_TOKEN);

        $this->assertTrue($libsql->isConnected());
    }

    public function test_authentication_with_wrong_token(): void
    {
        $this->expectException(ConnectionException::class);

        TestDriver::make(token: 'wrong token');
    }
}