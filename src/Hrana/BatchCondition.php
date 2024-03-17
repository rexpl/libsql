<?php

declare(strict_types=1);

namespace Rexpl\Libsql\Hrana;

readonly class BatchCondition
{
    /**
     * @param string $type
     * @param array<\Rexpl\Libsql\Hrana\BatchCondition|int> $extra
     */
    public function __construct(public string $type, public array $extra = []) {}

    /**
     * Prepare the version for the request.
     *
     * @return array
     */
    public function getConditionForRequest(): array
    {
        return match($this->type) {
            'ok' => ['type' => 'ok', 'step' => $this->extra[0]],
            'error' => ['type' => 'error', 'step' => $this->extra[0]],
            'not' => ['type' => 'not', 'step' => $this->extra[0]->getConditionForRequest()],
            'and' => ['type' => 'and', 'step' => \array_map(
                fn (BatchCondition $c): array => $c->getConditionForRequest(),
                $this->extra)
            ],
            'or' => ['type' => 'or', 'step' => \array_map(
                fn (BatchCondition $c): array => $c->getConditionForRequest(),
                $this->extra)
            ],
            'is_autocommit' => ['type' => 'is_autocommit'],
        };
    }
}