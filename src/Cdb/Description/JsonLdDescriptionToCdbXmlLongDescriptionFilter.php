<?php

namespace CultuurNet\UDB3\Cdb\Description;

use CultuurNet\UDB3\StringFilter\CombinedStringFilter;
use CultuurNet\UDB3\StringFilter\NewlineToBreakTagStringFilter;

class JsonLdDescriptionToCdbXmlLongDescriptionFilter extends CombinedStringFilter
{
    public function __construct()
    {
        // Convert any \n to a <br> tag.
        $nl2brFilter = new NewlineToBreakTagStringFilter();

        // Make sure \n is replaced with <br> and not <br />.
        $nl2brFilter->closeTag(false);

        $this->addFilter($nl2brFilter);
    }
}
