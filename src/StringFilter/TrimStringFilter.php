<?php

namespace CultuurNet\UDB3\StringFilter;

class TrimStringFilter implements StringFilterInterface
{
    public function filter($string)
    {
        return trim($string);
    }
}
