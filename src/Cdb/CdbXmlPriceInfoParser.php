<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Cdb;

use CultureFeed_Cdb_Data_Detail;
use CultureFeed_Cdb_Data_Price;
use CultuurNet\UDB3\Model\ValueObject\Price\Tariff;
use CultuurNet\UDB3\Model\ValueObject\Price\TariffName;
use CultuurNet\UDB3\Model\ValueObject\Price\Tariffs;
use CultuurNet\UDB3\Model\ValueObject\Price\TranslatedTariffName;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\MoneyFactory;
use CultuurNet\UDB3\PriceInfo\PriceInfo;
use Money\Currency;

final class CdbXmlPriceInfoParser
{
    private PriceDescriptionParser $priceDescriptionParser;

    public function __construct(PriceDescriptionParser $priceDescriptionParser)
    {
        $this->priceDescriptionParser = $priceDescriptionParser;
    }

    public function parse(\CultureFeed_Cdb_Data_DetailList $detailsList, Language $mainLanguage): ?PriceInfo
    {
        $detailsArray = [];
        foreach ($detailsList as $detail) {
            $detailsArray[] = $detail;
        }
        $details = $detailsArray;

        $mainLanguageDetails = array_filter(
            $details,
            function (\CultureFeed_Cdb_Data_Detail $detail) use ($mainLanguage) {
                return $detail->getLanguage() === $mainLanguage->getCode();
            }
        );

        /* @var \CultureFeed_Cdb_Data_EventDetail $mainLanguageDetail */
        $mainLanguageDetail = reset($mainLanguageDetails);
        if (!$mainLanguageDetail) {
            return null;
        }

        /** @var CultureFeed_Cdb_Data_Price|null $mainLanguagePrice */
        $mainLanguagePrice = $mainLanguageDetail->getPrice();

        if (!$mainLanguagePrice) {
            return null;
        }

        $basePrice = $mainLanguagePrice->getValue();
        if (!is_numeric($basePrice)) {
            return null;
        }

        $basePrice = (float) $basePrice;

        if ($basePrice < 0) {
            return null;
        }

        $basePrice = Tariff::createBasePrice(
            MoneyFactory::create($basePrice, new Currency('EUR'))
        );

        /* @var Tariff[] $tariffs */
        $tariffs = [];
        /** @var CultureFeed_Cdb_Data_Detail $detail */
        foreach ($details as $detail) {
            $price = null;

            $language = $detail->getLanguage();

            /** @var CultureFeed_Cdb_Data_Price|null $price */
            $price = $detail->getPrice();
            if (!$price) {
                continue;
            }

            $description = $price->getDescription();
            if (!$description) {
                continue;
            }

            $translatedTariffs = $this->priceDescriptionParser->parse($description);
            if (empty($translatedTariffs)) {
                continue;
            }

            // Skip the base price. We do not use array_shift() here, because it will not preserve keys when there are
            // only numeric keys left.
            reset($translatedTariffs);
            $basePriceKey = key($translatedTariffs);
            unset($translatedTariffs[$basePriceKey]);

            $tariffIndex = 0;
            foreach ($translatedTariffs as $tariffName => $tariffPrice) {
                if (!isset($tariffs[$tariffIndex])) {
                    $tariff = new Tariff(
                        new TranslatedTariffName(new Language($language), new TariffName((string) $tariffName)),
                        MoneyFactory::create($tariffPrice, new Currency('EUR'))
                    );
                } else {
                    $tariff = $tariffs[$tariffIndex];
                    $name = $tariff->getName();
                    $name = $name->withTranslation(new Language($language), new TariffName($tariffName));
                    $tariff = new Tariff(
                        $name,
                        $tariff->getPrice()
                    );
                }

                $tariffs[$tariffIndex] = $tariff;
                $tariffIndex++;
            }
        }

        return (new PriceInfo($basePrice))->withTariffs(new Tariffs(...$tariffs));
    }
}
