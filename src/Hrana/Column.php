<?php

declare(strict_types=1);

namespace Rexpl\Libsql\Hrana;

class Column
{
    /**
     * @param string|null $name
     * @param string|null $type
     */
    public function __construct(public ?string $name, public ?string $type) {}
}