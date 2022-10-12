<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Cdb\Description;

use CultuurNet\UDB3\StringFilter\StringFilterInterface;
use CultuurNet\UDB3\StringLiteral;

class ShortDescription extends StringLiteral
{
    /**
     * @param string $shortDescriptionAsString
     * @return ShortDescription
     */
    public static function fromCdbXmlToJsonLdFormat($shortDescriptionAsString)
    {
        return new ShortDescription(
            (new CdbXmlShortDescriptionToJsonLdFilter())->filter($shortDescriptionAsString)
        );
    }
}
