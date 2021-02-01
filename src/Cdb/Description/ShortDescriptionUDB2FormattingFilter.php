<?php

namespace CultuurNet\UDB3\Cdb\Description;

use CultuurNet\UDB3\StringFilter\StringFilterInterface;

class ShortDescriptionUDB2FormattingFilter implements StringFilterInterface
{
    /**
     * @param string $string
     * @return string
     */
    public function filter($string)
    {
        return strip_tags(html_entity_decode($string));
    }
}
