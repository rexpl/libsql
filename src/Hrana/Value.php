<?php

declare(strict_types=1);

namespace Rexpl\Libsql\Hrana;

class Value
{
    /**
     * @param string $type
     * @param mixed $value
     */
    public function __construct(public string $type, public mixed $value) {}

    /**
     * @param mixed $value
     *
     * @return static
     */
    public static function createForRequest(mixed $value): static
    {
        $result = match(\gettype($value)) {
            'integer' => new static('integer', (string) $value),
            'boolean' => new static('integer', $value ? '1' : '0'),
            'double' => new static('float', $value),
            'NULL' => new static('null', null),
            default => null,
        };

        // Found corresponding type ? If not only string is left.
        if ($result !== null) {
            return $result;
        }

        // Is blob ... ?
        if (\is_string($value) && !mb_detect_encoding($value, strict: true)) {
            return new static('blob', \base64_encode($value));
        }

        // We try to cast to string, allowing object to be serialized if possible.
        return new static('text', (string) $value);
    }

    /**
     * @param \stdClass $value
     *
     * @return static
     */
    public static function parseForResponse(\stdClass $value): static
    {
        return match ($value->type) {
            'text', 'float' => new static($value->type, $value->value),
            'integer' => new static('integer', (int) $value->value),
            'null' => new static('null', null),
            'blob' => new static('blob', \base64_decode($value->base64)),
        };
    }
}