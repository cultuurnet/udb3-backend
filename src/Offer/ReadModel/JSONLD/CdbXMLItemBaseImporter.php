<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\ReadModel\JSONLD;

use CultureFeed_Cdb_Item_Base;
use CultuurNet\UDB3\Cdb\CdbXmlPriceInfoParser;
use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Model\ValueObject\Moderation\WorkflowStatus;
use CultuurNet\UDB3\ReadModel\MultilingualJsonLDProjectorTrait;
use DateTimeZone;

class CdbXMLItemBaseImporter
{
    use MultilingualJsonLDProjectorTrait;

    /**
     * @var string[]
     */
    private array $basePriceTranslations;

    private CdbXmlPriceInfoParser $priceInfoParser;

    public function __construct(
        CdbXmlPriceInfoParser $priceInfoParser,
        array $basePriceTranslations
    ) {
        $this->priceInfoParser = $priceInfoParser;
        $this->basePriceTranslations = $basePriceTranslations;
    }


    public function importPublicationInfo(
        CultureFeed_Cdb_Item_Base $item,
        \stdClass $jsonLD
    ): void {
        $jsonLD->creator = $item->getCreatedBy();

        $itemCreationDate = $item->getCreationDate();

        if (!empty($itemCreationDate)) {
            $creationDate = DateTimeFactory::fromFormat(
                'Y-m-d?H:i:s',
                $itemCreationDate,
                new DateTimeZone('Europe/Brussels')
            );

            $jsonLD->created = $creationDate->format('c');
        }

        $itemLastUpdatedDate = $item->getLastUpdated();

        if (!empty($itemLastUpdatedDate)) {
            $lastUpdatedDate = DateTimeFactory::fromFormat(
                'Y-m-d?H:i:s',
                $itemLastUpdatedDate,
                new DateTimeZone('Europe/Brussels')
            );

            $jsonLD->modified = $lastUpdatedDate->format('c');
        }

        $jsonLD->publisher = $item->getOwner();
    }


    public function importAvailable(
        \CultureFeed_Cdb_Item_Base $item,
        \stdClass $jsonLD
    ): void {
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

    private function formatAvailableString(string $availableString): string
    {
        $available = DateTimeFactory::fromFormat(
            'Y-m-d?H:i:s',
            $availableString,
            new DateTimeZone('Europe/Brussels')
        );

        return $available->format('c');
    }


    public function importExternalId(
        \CultureFeed_Cdb_Item_Base $item,
        \stdClass $jsonLD
    ): void {
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


    public function importWorkflowStatus(
        CultureFeed_Cdb_Item_Base $item,
        \stdClass $jsonLD
    ): void {
        $wfStatus = $item->getWfStatus();

        $workflowStatus = $wfStatus ? WorkflowStatus::fromCultureFeedWorkflowStatus($wfStatus) : WorkflowStatus::READY_FOR_VALIDATION();

        $jsonLD->workflowStatus = $workflowStatus->toString();
    }

    /**
     * @param \CultureFeed_Cdb_Data_DetailList|\CultureFeed_Cdb_Data_Detail[] $details
     */
    public function importPriceInfo(
        \CultureFeed_Cdb_Data_DetailList $details,
        \stdClass $jsonLD
    ): void {
        $mainLanguage = $this->getMainLanguage($jsonLD);

        $priceInfo = $this->priceInfoParser->parse($details, $mainLanguage);
        if (!$priceInfo) {
            return;
        }

        $jsonLD->priceInfo = [
            [
                'category' => 'base',
                'name' => $this->basePriceTranslations,
                'price' => $priceInfo->getBasePrice()->getPrice()->getAmount() / 100,
                'priceCurrency' => $priceInfo->getBasePrice()->getCurrency()->getName(),
            ],
        ];

        foreach ($priceInfo->getTariffs() as $tariff) {
            $jsonLD->priceInfo[] = [
                'category' => 'tariff',
                'name' => $tariff->getName()->serialize(),
                'price' => $tariff->getPrice()->getAmount() / 100,
                'priceCurrency' => $tariff->getCurrency()->getName(),
            ];
        }
    }
}
