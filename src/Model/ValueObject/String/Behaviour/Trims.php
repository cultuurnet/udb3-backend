<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\String\Behaviour;

trait Trims
{
    private function trim($value, $characters = " \t\n\r\0\x0B")
    {
        /* @var IsString $this */
        $this->guardString($value);
        return trim($value, $characters);
    }

    private function trimLeft($value, $characters = " \t\n\r\0\x0B")
    {
        /* @var IsString $this */
        $this->guardString($value);
        return ltrim($value, $characters);
    }

    private function trimRight($value, $characters = " \t\n\r\0\x0B")
    {
        /* @var IsString $this */
        $this->guardString($value);
        return rtrim($value, $characters);
    }
}
