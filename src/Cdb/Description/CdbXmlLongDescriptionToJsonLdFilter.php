<?php

namespace CultuurNet\UDB3\Cdb\Description;

use CultuurNet\UDB3\StringFilter\BreakTagToNewlineStringFilter;
use CultuurNet\UDB3\StringFilter\CombinedStringFilter;
use CultuurNet\UDB3\StringFilter\ConsecutiveBlockOfTextStringFilter;
use CultuurNet\UDB3\StringFilter\StripSourceStringFilter;
use CultuurNet\UDB3\StringFilter\StripSurroundingSpaceStringFilter;

class CdbXmlLongDescriptionToJsonLdFilter extends CombinedStringFilter
{
    public function __construct()
    {
        // Remove obsolete <p class="uiv-source">...</p> tag.
        // Do NOT strip any other HTML markup! HTML is allowed for formatting.
        $this->addFilter(new StripSourceStringFilter());

        // Remove any surrounding spaces and put the whole string on a single line.
        $this->addFilter(new ConsecutiveBlockOfTextStringFilter());

        // Convert <br> and <br /> tags to newlines.
        $this->addFilter(new BreakTagToNewlineStringFilter());

        // Remove any surrounding space caused by converting <br /> to newlines.
        $this->addFilter(new StripSurroundingSpaceStringFilter());
    }
}
