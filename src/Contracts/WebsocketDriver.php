<?php

declare(strict_types=1);

namespace Rexpl\Libsql\Contracts;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\UriInterface;

interface WebsocketDriver
{
    /**
     * @param \Psr\Http\Message\UriInterface $uri
     * @param array<string,string> $headers
     *
     * @return \Psr\Http\Message\MessageInterface
     * @throws \Rexpl\Libsql\Exception\ConnectionException
     */
    public function connect(UriInterface $uri, array $headers): MessageInterface;

    /**
     * @return void
     * @throws \Rexpl\Libsql\Exception\ConnectionException
     */
    public function disconnect(): void;

    /**
     * @return bool
     * @throws \Rexpl\Libsql\Exception\ConnectionException
     */
    public function isConnected(): bool;

    /**
     * @param string $message
     *
     * @return string
     * @throws \Rexpl\Libsql\Exception\ConnectionException
     */
    public function textMessage(string $message): string;
}