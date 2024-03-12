<?php

declare(strict_types=1);

namespace Rexpl\Libsql\Websockets;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\UriInterface;
use Rexpl\Libsql\Contracts\WebsocketDriver;
use Rexpl\Libsql\Exception\ConnectionException;
use WebSocket\Client;
use WebSocket\Middleware\CloseHandler;
use WebSocket\Middleware\PingResponder;

class DefaultDriver implements WebsocketDriver
{
    /**
     * @var \WebSocket\Client|null
     */
    protected ?Client $connection = null;

    /**
     * @inheritDoc
     */
    public function connect(UriInterface $uri, array $headers): MessageInterface
    {
        try {
            $this->connection = new Client($uri);
            $this->connection->addMiddleware(new CloseHandler())
                ->addMiddleware(new PingResponder());

            foreach ($headers as $name => $value) {
                $this->connection->addHeader($name, $value);
            }

            $this->connection->connect();

        } catch (\Throwable $clientException) {
            throw new ConnectionException(
                \sprintf('Underlying websocket connection failed (%s).', $clientException->getMessage()),
                previous: $clientException
            );
        }

        return $this->connection->getHandshakeResponse();
    }

    /**
     * @inheritDoc
     */
    public function disconnect(): void
    {
        try {
            $this->connection?->close();
        } catch (\Throwable $clientException) {
            throw new ConnectionException(
                \sprintf('Failed closing underlying websocket connection (%s).', $clientException->getMessage()),
                previous: $clientException
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function isConnected(): bool
    {
        return (bool) $this->connection?->isConnected();
    }

    /**
     * @inheritDoc
     */
    public function textMessage(string $message): string
    {
        try {
            $this->connection->text($message);
            $response = $this->connection->receive();
        } catch (\Throwable $exception) {

            if ($this->connection->isConnected()) {
                $this->disconnect();
            }

            throw new ConnectionException($exception->getMessage(), previous: $exception);
        }

        return $response->getContent();
    }
}