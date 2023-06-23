<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\StringFilter;

class StripSourceStringFilter implements StringFilterInterface
{
    public function filter(string $string): string
    {
        return preg_replace('@<p class="uiv-source">.*?</p>@', '', $string);
    }
}
