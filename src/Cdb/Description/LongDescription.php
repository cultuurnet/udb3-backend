<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Cdb\Description;

use CultuurNet\UDB3\StringLiteral;

class LongDescription extends StringLiteral
{
    /**
     * @param string $longDescriptionAsString
     * @return LongDescription
     */
    public static function fromCdbXmlToJsonLdFormat($longDescriptionAsString)
    {
        return new LongDescription(
            (new CdbXmlLongDescriptionToJsonLdFilter())->filter($longDescriptionAsString)
        );
    }
}
