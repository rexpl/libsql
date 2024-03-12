<?php

declare(strict_types=1);

namespace Rexpl\Libsql\Contracts;

use Rexpl\Libsql\Hrana\Version;

interface Request
{
    /**
     * @param \Rexpl\Libsql\Hrana\Version $version
     *
     * @return array
     */
    public function getRequestForMessage(Version $version): array;
}