<?php

namespace CultuurNet\UDB3\ReadModel;

use CultuurNet\UDB3\Language;

interface JsonDocumentLanguageAnalyzerInterface
{
    /**
     * @param JsonDocument $jsonDocument
     * @return Language[]
     */
    public function determineAvailableLanguages(JsonDocument $jsonDocument);

    /**
     * @param JsonDocument $jsonDocument
     * @return Language[]
     */
    public function determineCompletedLanguages(JsonDocument $jsonDocument);
}
