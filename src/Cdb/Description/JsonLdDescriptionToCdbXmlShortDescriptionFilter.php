<?php

namespace CultuurNet\UDB3\Cdb\Description;

use CultuurNet\UDB3\StringFilter\CombinedStringFilter;
use CultuurNet\UDB3\StringFilter\TruncateStringFilter;

class JsonLdDescriptionToCdbXmlShortDescriptionFilter extends CombinedStringFilter
{
    public function __construct()
    {
        $this->addFilter(new ShortDescriptionUDB3FormattingFilter());

        $truncateFilter = new TruncateStringFilter(400);
        $truncateFilter->addEllipsis();
        $truncateFilter->spaceBeforeEllipsis();
        $truncateFilter->turnOnWordSafe(1);

        $this->addFilter($truncateFilter);
    }
}
