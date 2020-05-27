<?php

namespace CultuurNet\UDB3\ReadModel;

use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Language;

class JsonDocumentLanguageEnricher implements JsonDocumentMetaDataEnricherInterface
{
    /**
     * @var JsonDocumentLanguageAnalyzerInterface
     */
    private $languageAnalyzer;

    /**
     * @param JsonDocumentLanguageAnalyzerInterface $languageAnalyzer
     */
    public function __construct(
        JsonDocumentLanguageAnalyzerInterface $languageAnalyzer
    ) {
        $this->languageAnalyzer = $languageAnalyzer;
    }

    /**
     * @inheritdoc
     */
    public function enrich(JsonDocument $jsonDocument, Metadata $metadata)
    {
        $body = $jsonDocument->getBody();

        $castLanguageToString = function (Language $language) {
            return $language->getCode();
        };

        $availableLanguages = $this->languageAnalyzer->determineAvailableLanguages($jsonDocument);
        $completedLanguages = $this->languageAnalyzer->determineCompletedLanguages($jsonDocument);

        if (!empty($availableLanguages)) {
            $body->languages = array_map($castLanguageToString, $availableLanguages);
        }

        if (!empty($completedLanguages)) {
            $body->completedLanguages = array_map($castLanguageToString, $completedLanguages);
        }

        return $jsonDocument->withBody($body);
    }
}
