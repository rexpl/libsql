<?php

declare(strict_types=1);

namespace Rexpl\Libsql\Hrana;

class Statement
{
    /**
     * @param string $sql
     * @param array|null $arguments
     * @param bool $wantRows
     */
    public function __construct(public string $sql, public ?array $arguments, public bool $wantRows) {}

    /**
     * @param \Rexpl\Libsql\Hrana\Version $version
     *
     * @return array
     */
    public function getStatementForRequest(Version $version): array
    {
        $statement = ['sql' => $this->sql, 'want_rows' => $this->wantRows];

        if ($this->arguments === null) {
            return $statement;
        }

        foreach ($this->arguments as $key => $argument) {

            $value = $argument instanceof Value
                ? $argument
                : Value::createForRequest($argument);
            $valueArray = ['type' => $value->type];

            if ($value->type === 'blob') {
                $valueArray['base64'] = $value->value;
            } elseif ($value->type !== 'null') {
                $valueArray['value'] = $value->value;
            }

            if (\is_string($key)) {
                $statement['named_args'][] = ['name' => $key, 'value' => $valueArray];
            } else {
                $statement['args'][] = $valueArray;
            }
        }

        return $statement;
    }
}