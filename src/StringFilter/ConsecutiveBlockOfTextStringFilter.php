<?php

namespace CultuurNet\UDB3\StringFilter;

class ConsecutiveBlockOfTextStringFilter extends CombinedStringFilter
{
    public function __construct()
    {
        $this->addFilter(
            new StripSurroundingSpaceStringFilter()
        );
        $this->addFilter(
            new NewlineToSpaceStringFilter()
        );
        $this->addFilter(
            new TrimStringFilter()
        );
    }
}
