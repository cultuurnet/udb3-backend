<?php


namespace CultuurNet\UDB3\StringFilter;

class StripSourceStringFilter implements StringFilterInterface
{
    public function filter($string)
    {
        if (!is_string($string)) {
            throw new \InvalidArgumentException('Argument should be string, got ' . gettype($string) . ' instead.');
        }

        return preg_replace('@<p class="uiv-source">.*?</p>@', '', $string);
    }
}
