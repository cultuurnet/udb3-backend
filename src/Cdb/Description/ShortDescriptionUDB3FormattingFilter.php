<?php

namespace CultuurNet\UDB3\Cdb\Description;

use CultuurNet\UDB3\StringFilter\CombinedStringFilter;
use CultuurNet\UDB3\StringFilter\ConsecutiveBlockOfTextStringFilter;
use CultuurNet\UDB3\StringFilter\StripHtmlStringFilter;

class ShortDescriptionUDB3FormattingFilter extends CombinedStringFilter
{
    public function __construct()
    {
        // Remove any HTML.
        $this->addFilter(new StripHtmlStringFilter());

        // Put everything on a single line and trim surrounding whitespace.
        $this->addFilter(new ConsecutiveBlockOfTextStringFilter());
    }
}
