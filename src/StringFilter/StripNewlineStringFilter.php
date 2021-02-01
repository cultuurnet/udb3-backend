<?php

namespace CultuurNet\UDB3\StringFilter;

class StripNewlineStringFilter implements StringFilterInterface
{
    /**
     * @param string $string
     * @return string
     */
    public function filter($string)
    {
        if (!is_string($string)) {
            throw new \InvalidArgumentException('Argument should be string, got ' . gettype($string) . ' instead.');
        }

        return preg_replace("/[\\r\\n]+/", "", $string);
    }
}
