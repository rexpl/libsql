<?php

declare(strict_types=1);

namespace Rexpl\Libsql\Tests;

use PHPUnit\Framework\TestCase;
use Rexpl\Libsql\Hrana\Value;

class ValueTest extends TestCase
{
    public function test_null_in_request(): void
    {
        $value = Value::createForRequest(null);

        $this->assertNull($value->value);
        $this->assertTrue($value->type === 'null');
    }

    public function test_null_in_response(): void
    {
        $response = new \stdClass();
        $response->type = 'null';

        $value = Value::parseForResponse($response);

        $this->assertNull($value->value);
        $this->assertTrue($value->type === 'null');
    }

    public function test_bool_in_request(): void
    {
        $value = Value::createForRequest(true);

        $this->assertIsString($value->value);
        $this->assertTrue($value->type === 'integer');
    }

    public function test_integer_in_request(): void
    {
        $value = Value::createForRequest(34);

        // https://github.com/tursodatabase/libsql/blob/main/docs/HRANA_3_SPEC.md#values
        $this->assertIsString($value->value);
        $this->assertTrue($value->type === 'integer');
    }

    public function test_integer_in_response(): void
    {
        $response = new \stdClass();
        $response->type = 'integer';
        $response->value = '34';

        $value = Value::parseForResponse($response);

        $this->assertIsInt($value->value);
        $this->assertTrue($value->type === 'integer');
    }

    public function test_float_in_request(): void
    {
        $value = Value::createForRequest(76.29);

        $this->assertIsFloat($value->value);
        $this->assertTrue($value->type === 'float');
    }

    public function test_float_in_response(): void
    {
        $response = new \stdClass();
        $response->type = 'float';
        $response->value = 76.29;

        $value = Value::parseForResponse($response);

        $this->assertIsFloat($value->value);
        $this->assertTrue($value->type === 'float');
    }

    public function test_string_in_request(): void
    {
        $value = Value::createForRequest('test');

        $this->assertIsString($value->value);
        $this->assertTrue($value->type === 'text');
    }

    public function test_string_in_response(): void
    {
        $response = new \stdClass();
        $response->type = 'text';
        $response->value = 'test';

        $value = Value::parseForResponse($response);

        $this->assertIsString($value->value);
        $this->assertTrue($value->type === 'text');
    }

    public function test_blob_in_request(): void
    {
        $value = Value::createForRequest(\random_bytes(16));

        $this->assertTrue(
            false !== base64_decode($value->value, true),
            'Failed asserting that a blob type was detected.'
        );

        $this->assertTrue($value->type === 'blob');
    }

    public function test_blob_in_response(): void
    {
        $bytes = \random_bytes(16);

        $response = new \stdClass();
        $response->type = 'blob';
        $response->base64 = \base64_encode($bytes);

        $value = Value::parseForResponse($response);

        $this->assertEquals($bytes, $value->value);
        $this->assertTrue($value->type === 'blob');
    }

    public function test_string_object_in_request(): void
    {
        $object = new class {
            public function __toString(): string
            {
                return static::class;
            }
        };

        $value = Value::createForRequest($object);

        $this->assertIsString($value->value);
        $this->assertTrue($value->type === 'text');
    }
}