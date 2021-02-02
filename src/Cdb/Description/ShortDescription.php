<?php

namespace CultuurNet\UDB3\Cdb\Description;

use CultuurNet\UDB3\StringFilter\StringFilterInterface;
use ValueObjects\StringLiteral\StringLiteral;

class ShortDescription extends StringLiteral
{
    /**
     * @var StringFilterInterface
     */
    private static $cdbXmlToJsonLdFilter;

    /**
     * @param string $shortDescriptionAsString
     * @return ShortDescription
     */
    public static function fromCdbXmlToJsonLdFormat($shortDescriptionAsString)
    {
        $cdbXmlToJsonLdFilter = self::getCdbXmlToJsonLdFilter();

        return new ShortDescription(
            $cdbXmlToJsonLdFilter->filter($shortDescriptionAsString)
        );
    }

    /**
     * @return StringFilterInterface
     */
    private static function getCdbXmlToJsonLdFilter()
    {
        if (!isset(self::$cdbXmlToJsonLdFilter)) {
            self::$cdbXmlToJsonLdFilter = new CdbXmlShortDescriptionToJsonLdFilter();
        }
        return self::$cdbXmlToJsonLdFilter;
    }
}
