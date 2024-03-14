<?php

declare(strict_types=1);

namespace Rexpl\Libsql\Tests\test_driver;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\UriInterface;
use Rexpl\Libsql\Contracts\WebsocketDriver;
use Rexpl\Libsql\Libsql;
use Rexpl\Libsql\Tests\integration\AuthTest;

/**
 * Really janky way to simulate a libsql server allowing some kind of test :) ...
 */
class TestDriver implements WebsocketDriver
{
    protected bool $isConnected;

    /**
     * @inheritDoc
     */
    public function connect(UriInterface $uri, array $headers): MessageInterface
    {
        $this->isConnected = true;

        return new TestResponse();
    }

    /**
     * @inheritDoc
     */
    public function disconnect(): void
    {
        $this->isConnected = false;
    }

    /**
     * @inheritDoc
     */
    public function isConnected(): bool
    {
        return $this->isConnected;
    }

    /**
     * @inheritDoc
     */
    public function textMessage(string $message): string
    {
        $message = \json_decode($message);

        $response = match ($message->type) {
            'hello' => $this->respondHello($message),
            'request' => $this->respondRequest($message),
        };

        return \json_encode($response);
    }

    protected function respondHello(\stdClass $message): array
    {
        if ($message->jwt === null || $message->jwt === AuthTest::VALID_AUTH_TOKEN) {
            return ['type' => 'hello_ok'];
        }

        return [
            'type' => 'hello_error',
            'error' => [
                'message' => 'invalid auth token',
                'code' => 'BAD_AUTH'
            ],
        ];
    }

    protected function respondRequest(\stdClass $message): array
    {
        return match ($message->request->type) {
            'open_stream' => $this->respondOpenStreamRequest($message->request),
            'close_stream' => $this->respondCloseStreamRequest($message->request),
            'execute' => $this->responseExecuteRequest($message->request),
            'get_autocommit' => $this->responseGetAutoCommit(),
        };
    }

    protected function returnResponseOk(array $response): array
    {
        return [
            'type' => 'response_ok',
            'response' => $response,
        ];
    }

    protected function returnResponseError(?string $message = null): array
    {
        return [
            'type' => 'response_error',
            'error' => [
                'message' => $message ?: 'Error',
                'code' => 'ERROR_CODE',
            ],
        ];
    }

    protected function respondOpenStreamRequest(\stdClass $request): array
    {
        return $this->returnResponseOk([
            'type' => 'open_stream',
        ]);
    }

    protected function respondCloseStreamRequest(\stdClass $request): array
    {
        return $this->returnResponseOk([
            'type' => 'close_stream',
        ]);
    }

    protected function responseExecuteRequest(\stdClass $request): array
    {
        $stmt = $request->stmt;
        $query = @\unserialize($stmt->sql);

        if ($query === false) {
            return $this->returnResponseError('Invalid query');
        }

        if (false === $stmt->want_rows) {
            return $this->returnResponseOk([
                'type' => 'execute',
                'result' => [
                    'cols' => [],
                    'rows' => [],
                    'affected_row_count' => $query->rowCount ?? 0,
                    'last_insert_rowid' => (string)($query->lastInsertId ?? null),
                ],
            ]);
        }

        $requestedCols = $query->cols ?? ['name', 'profile_picture', 'age', 'score', 'address'];
        $requestedRowCount = $query->rowCount ?? 10;

        $faker = \Faker\Factory::create();

        $rows = [];

        for ($i = 0; $i < $requestedRowCount; $i++) {
            foreach ($requestedCols as $col) {
                $rows[$i][] = match ($col) {
                    'name' => ['type' => 'text', 'value' => $faker->name()],
                    'profile_picture' => ['type' => 'blob', 'base64' => \base64_encode(\random_bytes(128))],
                    'age' => ['type' => 'integer', 'value' => $faker->randomDigit()],
                    'score' => ['type' => 'float', 'value' => $faker->randomFloat()],
                    default => ['type' => 'null'],
                };
            }
        }

        $cols = [];

        foreach ($requestedCols as $col) {
            $cols[] = match ($col) {
                'name' => ['name' => 'name', 'decltype' => 'text'],
                'profile_picture' => ['name' => 'profile_picture', 'decltype' => 'blob'],
                'age' => ['name' => 'age', 'decltype' => 'integer'],
                'score' => ['name' => 'score', 'decltype' => 'float'],
                default => ['name' => 'address', 'decltype' => 'null'],
            };
        }

        return $this->returnResponseOk([
            'type' => 'execute',
            'result' => [
                'cols' => $cols,
                'rows' => \array_values($rows),
                'affected_row_count' => $query->rowCount ?? 0,
                'last_insert_rowid' => $query->lastINsertId ?? null,
            ],
        ]);
    }

    protected function responseGetAutoCommit(): array
    {
        return $this->returnResponseOk([
            'type' => 'get_autocommit',
            'is_autocommit' => true,
        ]);
    }

    public static function make(string $url = 'libsql://127.0.0.1:8080', ?string $token = null, bool $secure = true): Libsql
    {
        return new Libsql($url, $token, $secure, new TestDriver());
    }
}