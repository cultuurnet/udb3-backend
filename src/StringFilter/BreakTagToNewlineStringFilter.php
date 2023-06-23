<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\StringFilter;

class BreakTagToNewlineStringFilter implements StringFilterInterface
{
    public function filter(string $string): string
    {
        return preg_replace('@<br ?/?>@', "\n", $string);
    }
}
