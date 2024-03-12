<?php

declare(strict_types=1);

namespace Rexpl\Libsql\Hrana\Message\Client;

use Rexpl\Libsql\Contracts\ClientMessage;
use Rexpl\Libsql\Hrana\Version;

class HelloMessage implements ClientMessage
{
    /**
     * @param \SensitiveParameterValue $jwt
     */
    public function __construct(public \SensitiveParameterValue $jwt) {}

    /**
     * @param \Rexpl\Libsql\Hrana\Version $version
     *
     * @return array
     */
    public function getMessage(Version $version): array
    {
        return [
            'type' => 'hello',
            'jwt' => $this->jwt->getValue(),
        ];
    }
}