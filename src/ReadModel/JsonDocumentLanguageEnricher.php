<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\ReadModel;

use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;

class JsonDocumentLanguageEnricher implements JsonDocumentMetaDataEnricherInterface
{
    private JsonDocumentLanguageAnalyzerInterface $languageAnalyzer;


    public function __construct(
        JsonDocumentLanguageAnalyzerInterface $languageAnalyzer
    ) {
        $this->languageAnalyzer = $languageAnalyzer;
    }

    public function enrich(JsonDocument $jsonDocument, Metadata $metadata): JsonDocument
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
