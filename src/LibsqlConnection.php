<?php

declare(strict_types=1);

namespace Rexpl\Libsql;

use Phrity\Net\UriFactory;
use Rexpl\Libsql\Contracts\ClientMessage;
use Rexpl\Libsql\Contracts\ServerMessage;
use Rexpl\Libsql\Contracts\WebsocketDriver;
use Rexpl\Libsql\Exception\ConnectionException;
use Rexpl\Libsql\Hrana\Message\Client\HelloMessage;
use Rexpl\Libsql\Hrana\Message\Server\HelloErrorMessage;
use Rexpl\Libsql\Hrana\Message\Server\HelloOkMessage;
use Rexpl\Libsql\Hrana\Message\Server\ResponseErrorMessage;
use Rexpl\Libsql\Hrana\Message\Server\ResponseOkMessage;
use Rexpl\Libsql\Hrana\Version;
use Rexpl\Libsql\Websockets\WebsocketDriverFactory;

class LibsqlConnection
{
    /**
     * @var \Rexpl\Libsql\Contracts\WebsocketDriver
     */
    protected WebsocketDriver $driver;

    /**
     * @var \Rexpl\Libsql\Hrana\Version
     */
    protected Version $version;

    /**
     * @var int
     */
    protected int $streamIdIncrementor = 1;

    /**
     * @param \Rexpl\Libsql\Contracts\WebsocketDriver|null $driver
     */
    public function __construct(?WebsocketDriver $driver)
    {
        $this->driver = $driver ?: WebsocketDriverFactory::create();
    }

    /**
     * @param string $url
     * @param \SensitiveParameterValue $token
     * @param bool $secure
     *
     * @return Version The negotiated protocol version.
     */
    public function connect(string $url, \SensitiveParameterValue $token, bool $secure): Version
    {
        $uri = (new UriFactory())->createUri($url);

        if ($uri->getScheme() === 'libsql') {
            $uri = $uri->withScheme($secure ? 'wss' : 'ws');
        }

        $response = $this->driver->connect($uri, [
            'Sec-WebSocket-Protocol' => 'hrana3',
            'X-Libsql-Client-Version' => \sprintf('libsql-remote-php-%s', Libsql::VERSION),
        ]);

        if (
            $response->hasHeader('Sec-WebSocket-Protocol')
            && $response->getHeader('Sec-WebSocket-Protocol')[0] === 'hrana3'
        ) {
            $this->version = Version::HRANA_3;
        } else {
            $this->disconnect();
            throw new ConnectionException('Unsupported libsql server version.');
        }

        $message = $this->send(new HelloMessage($token));

        if ($message instanceof HelloOkMessage) {
            return $this->version;
        }

        $this->disconnect();

        if ($message instanceof HelloErrorMessage) {
            $message->error->throw(ConnectionException::class);
        }

        throw new \LogicException('Invalid protocol implementation.');
    }

    public function isConnected(): bool
    {
        return $this->driver->isConnected();
    }

    public function getNextStreamId(): int
    {
        return $this->streamIdIncrementor++;
    }

    public function disconnect(): void
    {
        if (!$this->driver->isConnected()) {
            return;
        }

        $this->driver->disconnect();
    }

    /**
     * @param \Rexpl\Libsql\Contracts\ClientMessage $message
     *
     * @return \Rexpl\Libsql\Contracts\ServerMessage
     */
    public function send(ClientMessage $message): ServerMessage
    {
        $preparedMessage = $message->getMessage($this->version);
        $jsonMessage = \json_encode($preparedMessage);

        $receivedJsonMessage = $this->driver->textMessage($jsonMessage);

        $receivedMessage = \json_decode($receivedJsonMessage);

        return match ($receivedMessage->type) {
            'hello_ok' => HelloOkMessage::parseMessage($this->version, $receivedMessage),
            'hello_error' => HelloErrorMessage::parseMessage($this->version, $receivedMessage),
            'response_ok' => ResponseOkMessage::parseMessage($this->version, $receivedMessage),
            'response_error' => ResponseErrorMessage::parseMessage($this->version, $receivedMessage),
            default => throw new \LogicException('Invalid protocol implementation.'),
        };
    }

    public function __destruct()
    {
        $this->disconnect();
    }
}