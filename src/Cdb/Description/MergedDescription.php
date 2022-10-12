<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Cdb\Description;

use CultuurNet\UDB3\StringFilter\StringFilterInterface;
use CultuurNet\UDB3\StringLiteral;

class MergedDescription extends StringLiteral
{
    /**
     * @return MergedDescription
     * @throws \InvalidArgumentException
     */
    public static function fromCdbDetail(\CultureFeed_Cdb_Data_Detail $detail)
    {
        $longDescription = $detail->getLongDescription();
        if ($longDescription) {
            $longDescription = LongDescription::fromCdbXmlToJsonLdFormat($longDescription);
        }

        $shortDescription = $detail->getShortDescription();
        if ($shortDescription) {
            $shortDescription = ShortDescription::fromCdbXmlToJsonLdFormat($shortDescription);
        }

        if ($longDescription && $shortDescription) {
            return MergedDescription::merge($shortDescription, $longDescription);
        }

        if ($longDescription) {
            return new MergedDescription($longDescription->toNative());
        }

        if ($shortDescription) {
            return new MergedDescription($shortDescription->toNative());
        }

        throw new \InvalidArgumentException(
            'Could not create MergedDescription object from given ' . get_class($detail) . '.'
        );
    }

    /**
     * @return MergedDescription $longDescription
     */
    public static function merge(ShortDescription $shortDescription, LongDescription $longDescription)
    {
        $shortAsString = $shortDescription->toNative();
        $longAsString = $longDescription->toNative();

        $shortAsStringWithoutEllipsis = rtrim($shortAsString, '. ');

        $longFormattedAsUdb2Short = (new ShortDescriptionUDB2FormattingFilter())->filter($longAsString);
        $longFormattedAsUdb3Short = (new ShortDescriptionUDB3FormattingFilter())->filter($longAsString);

        $udb2Comparison = strncmp(
            $longFormattedAsUdb2Short,
            $shortAsStringWithoutEllipsis,
            mb_strlen($shortAsStringWithoutEllipsis)
        );

        $udb3Comparison = strncmp(
            $longFormattedAsUdb3Short,
            $shortAsStringWithoutEllipsis,
            mb_strlen($shortAsStringWithoutEllipsis)
        );

        $shortIncludedInLong = $udb2Comparison === 0 || $udb3Comparison === 0;

        if ($shortIncludedInLong) {
            return new MergedDescription($longAsString);
        } else {
            return new MergedDescription($shortAsString . PHP_EOL . PHP_EOL . $longAsString);
        }
    }
}
