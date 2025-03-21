<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\ReadModel\JSONLD;

use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\ReadModel\ConfigurableJsonDocumentLanguageAnalyzer;
use CultuurNet\UDB3\ReadModel\JsonDocument;

class OrganizerJsonDocumentLanguageAnalyzer extends ConfigurableJsonDocumentLanguageAnalyzer
{
    public function __construct()
    {
        parent::__construct(
            [
                'name',
                'address',
                'description',
                'educationalDescription',
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

        if (is_string($body->name)) {
            $body->name = (object) [
                $mainLanguage => $body->name,
            ];
        }

        if (isset($body->address->streetAddress)) {
            $body->address = (object) [
                $mainLanguage => $body->address,
            ];
        }

        return $jsonDocument->withBody($body);
    }
}
