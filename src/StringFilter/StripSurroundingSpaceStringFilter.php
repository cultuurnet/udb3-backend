<?php

namespace CultuurNet\UDB3\StringFilter;

class StripSurroundingSpaceStringFilter extends CombinedStringFilter
{
    public function __construct()
    {
        $this->addFilter(
            new StripLeadingSpaceStringFilter()
        );
        $this->addFilter(
            new StripTrailingSpaceStringFilter()
        );
        $this->addFilter(
            new TrimStringFilter()
        );
    }
}
