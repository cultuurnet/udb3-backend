<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\String\Behaviour;

trait Trims
{
    private function trim(string $value, string $characters = " \t\n\r\0\x0B"): string
    {
        /* @var IsString $this */
        $this->guardString($value);
        return trim($value, $characters);
    }

    private function trimLeft(string $value, string $characters = " \t\n\r\0\x0B"): string
    {
        /* @var IsString $this */
        $this->guardString($value);
        return ltrim($value, $characters);
    }

    private function trimRight(string $value, string  $characters = " \t\n\r\0\x0B"): string
    {
        /* @var IsString $this */
        $this->guardString($value);
        return rtrim($value, $characters);
    }
}
