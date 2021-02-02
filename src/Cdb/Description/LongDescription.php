<?php

namespace CultuurNet\UDB3\Cdb\Description;

use CultuurNet\UDB3\StringFilter\StringFilterInterface;
use ValueObjects\StringLiteral\StringLiteral;

class LongDescription extends StringLiteral
{
    /**
     * @var StringFilterInterface
     */
    private static $cdbXmlToJsonLdFilter;

    /**
     * @param string $longDescriptionAsString
     * @return LongDescription
     */
    public static function fromCdbXmlToJsonLdFormat($longDescriptionAsString)
    {
        $cdbXmlToJsonLdFilter = self::getCdbXmlToJsonLdFilter();

        return new LongDescription(
            $cdbXmlToJsonLdFilter->filter($longDescriptionAsString)
        );
    }

    /**
     * @return StringFilterInterface
     */
    private static function getCdbXmlToJsonLdFilter()
    {
        if (!isset(self::$cdbXmlToJsonLdFilter)) {
            self::$cdbXmlToJsonLdFilter = new CdbXmlLongDescriptionToJsonLdFilter();
        }
        return self::$cdbXmlToJsonLdFilter;
    }
}
