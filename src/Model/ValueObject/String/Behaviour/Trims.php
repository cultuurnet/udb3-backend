<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\String\Behaviour;

trait Trims
{
    private function trim(string $value, string $characters = " \t\n\r\0\x0B"): string
    {
        return trim($value, $characters);
    }

    private function trimLeft(string $value, string $characters = " \t\n\r\0\x0B"): string
    {
        return ltrim($value, $characters);
    }

    private function trimRight(string $value, string  $characters = " \t\n\r\0\x0B"): string
    {
        return rtrim($value, $characters);
    }
}
