<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Cdb\Description;

use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\IsString;

final class MergedDescription
{
    use IsString;

    public function __construct(string $value)
    {
        $this->setValue($value);
    }

    /**
     * @throws \InvalidArgumentException
     */
    public static function fromCdbDetail(\CultureFeed_Cdb_Data_Detail $detail): MergedDescription
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
            return self::merge($shortDescription, $longDescription);
        }

        if ($longDescription) {
            return new self($longDescription->toString());
        }

        if ($shortDescription) {
            return new self($shortDescription->toString());
        }

        throw new \InvalidArgumentException(
            'Could not create MergedDescription object from given ' . get_class($detail) . '.'
        );
    }

    public static function merge(ShortDescription $shortDescription, LongDescription $longDescription): MergedDescription
    {
        $shortAsString = $shortDescription->toString();
        $shortAsStringWithoutEllipsis = rtrim($shortAsString, '. ');

        $longFormattedAsUdb2Short = (new ShortDescriptionUDB2FormattingFilter())->filter($longDescription->toString());
        $longFormattedAsUdb3Short = (new ShortDescriptionUDB3FormattingFilter())->filter($longDescription->toString());

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
            return new self($longDescription->toString());
        } else {
            return new self($shortAsString . PHP_EOL . PHP_EOL . $longDescription->toString());
        }
    }
}
