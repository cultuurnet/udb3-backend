<?php

namespace CultuurNet\UDB3\Offer\ReadModel\JSONLD;

use CultureFeed_Cdb_Item_Base;
use CultuurNet\UDB3\Cdb\DateTimeFactory;
use CultuurNet\UDB3\Cdb\PriceDescriptionParser;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Offer\WorkflowStatus;
use CultuurNet\UDB3\PriceInfo\BasePrice;
use CultuurNet\UDB3\PriceInfo\Price;
use CultuurNet\UDB3\PriceInfo\Tariff;
use CultuurNet\UDB3\ReadModel\MultilingualJsonLDProjectorTrait;
use CultuurNet\UDB3\ValueObject\MultilingualString;
use ValueObjects\Money\Currency;
use ValueObjects\StringLiteral\StringLiteral;

class CdbXMLItemBaseImporter
{
    use MultilingualJsonLDProjectorTrait;

    /**
     * @var PriceDescriptionParser
     */
    private $priceDescriptionParser;

    /**
     * @var string[]
     */
    private $basePriceTranslations;

    /**
     * @param PriceDescriptionParser $priceDescriptionParser
     * @param array $basePriceTranslations
     */
    public function __construct(
        PriceDescriptionParser $priceDescriptionParser,
        array $basePriceTranslations
    ) {
        $this->priceDescriptionParser = $priceDescriptionParser;
        $this->basePriceTranslations = $basePriceTranslations;
    }

    /**
     * @param CultureFeed_Cdb_Item_Base $item
     * @param \stdClass $jsonLD
     */
    public function importPublicationInfo(
        CultureFeed_Cdb_Item_Base $item,
        \stdClass $jsonLD
    ) {
        $jsonLD->creator = $item->getCreatedBy();

        $itemCreationDate = $item->getCreationDate();

        if (!empty($itemCreationDate)) {
            // format using ISO-8601 with time zone designator
            $creationDate = DateTimeFactory::dateTimeFromDateString(
                $itemCreationDate
            );

            $jsonLD->created = $creationDate->format('c');
        }

        $itemLastUpdatedDate = $item->getLastUpdated();

        if (!empty($itemLastUpdatedDate)) {
            $lastUpdatedDate = DateTimeFactory::dateTimeFromDateString(
                $itemLastUpdatedDate
            );

            $jsonLD->modified = $lastUpdatedDate->format('c');
        }

        $jsonLD->publisher = $item->getOwner();
    }

    /**
     * @param CultureFeed_Cdb_Item_Base $item
     * @param \stdClass $jsonLD
     */
    public function importAvailable(
        \CultureFeed_Cdb_Item_Base $item,
        \stdClass $jsonLD
    ) {
        $availableFromString = $item->getAvailableFrom();
        if ($availableFromString) {
            $jsonLD->availableFrom = $this->formatAvailableString(
                $availableFromString
            );
        }

        $availableToString = $item->getAvailableTo();
        if ($availableToString) {
            $jsonLD->availableTo = $this->formatAvailableString(
                $availableToString
            );
        }
    }

    /**
     * @param string $availableString
     * @return string
     */
    private function formatAvailableString($availableString)
    {
        $available = DateTimeFactory::dateTimeFromDateString(
            $availableString
        );

        return $available->format('c');
    }

    /**
     * @param CultureFeed_Cdb_Item_Base $item
     * @param \stdClass $jsonLD
     */
    public function importExternalId(
        \CultureFeed_Cdb_Item_Base $item,
        \stdClass $jsonLD
    ) {
        $externalId = $item->getExternalId();
        if (empty($externalId)) {
            return;
        }

        $externalIdIsCDB = (strpos($externalId, 'CDB:') === 0);

        if (!property_exists($jsonLD, 'sameAs')) {
            $jsonLD->sameAs = [];
        }

        if (!$externalIdIsCDB) {
            if (!in_array($externalId, $jsonLD->sameAs)) {
                array_push($jsonLD->sameAs, $externalId);
            }
        }
    }

    /**
     * @param CultureFeed_Cdb_Item_Base $item
     * @param \stdClass $jsonLD
     */
    public function importWorkflowStatus(
        CultureFeed_Cdb_Item_Base $item,
        \stdClass $jsonLD
    ) {
        $wfStatus = $item->getWfStatus();

        $workflowStatus = $wfStatus ? WorkflowStatus::fromNative($wfStatus) : WorkflowStatus::READY_FOR_VALIDATION();

        $jsonLD->workflowStatus = $workflowStatus->getName();
    }

    /**
     * @param \CultureFeed_Cdb_Data_DetailList|\CultureFeed_Cdb_Data_Detail[] $details
     * @param \stdClass $jsonLD
     */
    public function importPriceInfo(
        \CultureFeed_Cdb_Data_DetailList $details,
        $jsonLD
    ) {
        $mainLanguage = $this->getMainLanguage($jsonLD);

        $detailsArray = [];
        foreach ($details as $detail) {
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
            return;
        }

        $mainLanguagePrice = $mainLanguageDetail->getPrice();

        if (!$mainLanguagePrice) {
            return;
        }

        $basePrice = $mainLanguagePrice->getValue();
        if (!is_numeric($basePrice) || is_null($basePrice)) {
            return;
        }

        $basePrice = floatval($basePrice);

        if ($basePrice < 0) {
            return;
        }

        $basePrice = new BasePrice(
            Price::fromFloat($basePrice),
            Currency::fromNative('EUR')
        );

        /* @var Tariff[] $tariffs */
        $tariffs = [];
        foreach ($details as $detail) {
            $language = null;
            $price = null;
            $description = null;

            $language = $detail->getLanguage();

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
                        new MultilingualString(new Language($language), new StringLiteral((string) $tariffName)),
                        Price::fromFloat($tariffPrice),
                        Currency::fromNative('EUR')
                    );
                } else {
                    $tariff = $tariffs[$tariffIndex];
                    $name = $tariff->getName();
                    $name = $name->withTranslation(new Language($language), new StringLiteral((string) $tariffName));
                    $tariff = new Tariff(
                        $name,
                        $tariff->getPrice(),
                        $tariff->getCurrency()
                    );
                }

                $tariffs[$tariffIndex] = $tariff;
                $tariffIndex++;
            }
        }

        $jsonLD->priceInfo = [
            [
                'category' => 'base',
                'name' => $this->basePriceTranslations,
                'price' => $basePrice->getPrice()->toFloat(),
                'priceCurrency' => $basePrice->getCurrency()->getCode()->toNative(),
            ],
        ];

        foreach ($tariffs as $tariff) {
            $jsonLD->priceInfo[] = [
                'category' => 'tariff',
                'name' => $tariff->getName()->serialize(),
                'price' => $tariff->getPrice()->toFloat(),
                'priceCurrency' => $tariff->getCurrency()->getCode()->toNative(),
            ];
        }
    }
}
