<?php

declare(strict_types=1);

namespace Rexpl\Libsql\Hrana;

class Error
{
    /**
     * @param string $message
     * @param string $code
     */
    public function __construct(public string $message, public string $code) {}


    public function throw(string $exception): never
    {
        throw new $exception(\sprintf('%s (%s)', $this->message, $this->code));
    }
}