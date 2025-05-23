<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ReadModel\JSONLD;

use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\ReadModel\ConfigurableJsonDocumentLanguageAnalyzer;
use CultuurNet\UDB3\ReadModel\JsonDocument;

class EventJsonDocumentLanguageAnalyzer extends ConfigurableJsonDocumentLanguageAnalyzer
{
    public function __construct()
    {
        parent::__construct(
            [
                'name',
                'description',
                'bookingInfo.urlLabel',
                'priceInfo.[].name',
            ]
        );
    }

    /**
     * @todo Remove when full replay is done.
     * @replay_i18n
     * @see https://jira.uitdatabank.be/browse/III-2201
     *
     * @return Language[]
     */
    public function determineAvailableLanguages(JsonDocument $jsonDocument): array
    {
        $jsonDocument = $this->polyFillMultilingualFields($jsonDocument);
        return parent::determineAvailableLanguages($jsonDocument);
    }

    /**
     * @todo Remove when full replay is done.
     * @replay_i18n
     * @see https://jira.uitdatabank.be/browse/III-2201
     *
     * @return Language[]
     */
    public function determineCompletedLanguages(JsonDocument $jsonDocument): array
    {
        $jsonDocument = $this->polyFillMultilingualFields($jsonDocument);
        return parent::determineCompletedLanguages($jsonDocument);
    }

    /**
     * @todo Remove when full replay is done.
     * @replay_i18n
     * @see https://jira.uitdatabank.be/browse/III-2201
     *
     */
    private function polyFillMultilingualFields(JsonDocument $jsonDocument): JsonDocument
    {
        $body = $jsonDocument->getBody();
        $mainLanguage = isset($body->mainLanguage) ? $body->mainLanguage : 'nl';

        if (isset($body->bookingInfo->urlLabel) && is_string($body->bookingInfo->urlLabel)) {
            $body->bookingInfo->urlLabel = (object) [
                $mainLanguage => $body->bookingInfo->urlLabel,
            ];
        }

        if (isset($body->priceInfo) && is_array($body->priceInfo) && is_string($body->priceInfo[0]->name)) {
            foreach ($body->priceInfo as $priceInfo) {
                $priceInfo->name = (object) [
                    $mainLanguage => $priceInfo->name,
                ];
            }
        }

        return $jsonDocument->withBody($body);
    }
}
