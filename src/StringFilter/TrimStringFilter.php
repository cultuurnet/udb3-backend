<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\StringFilter;

class TrimStringFilter implements StringFilterInterface
{
    public function filter(string $string): string
    {
        return trim($string);
    }
}
