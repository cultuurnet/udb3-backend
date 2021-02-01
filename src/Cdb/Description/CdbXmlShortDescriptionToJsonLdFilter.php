<?php

namespace CultuurNet\UDB3\Cdb\Description;

use CultuurNet\UDB3\StringFilter\CombinedStringFilter;
use CultuurNet\UDB3\StringFilter\ConsecutiveBlockOfTextStringFilter;

class CdbXmlShortDescriptionToJsonLdFilter extends CombinedStringFilter
{
    public function __construct()
    {
        // Remove any surrounding spaces and put the whole string on a single line.
        $this->addFilter(new ConsecutiveBlockOfTextStringFilter());
    }
}
