<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\StringFilter;

class StripLeadingSpaceStringFilter implements StringFilterInterface
{
    public function filter(string $string): string
    {
        return preg_replace('/^[ \t]+/m', '', $string);
    }
}
