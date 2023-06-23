<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Cdb\Description;

use CultuurNet\UDB3\StringFilter\StringFilterInterface;

class ShortDescriptionUDB2FormattingFilter implements StringFilterInterface
{
    public function filter(string $string): string
    {
        return strip_tags(html_entity_decode($string));
    }
}
