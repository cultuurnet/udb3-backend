<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\ReadModel;

use CultuurNet\UDB3\Language;

interface JsonDocumentLanguageAnalyzerInterface
{
    /**
     * @return Language[]
     */
    public function determineAvailableLanguages(JsonDocument $jsonDocument): array;

    /**
     * @return Language[]
     */
    public function determineCompletedLanguages(JsonDocument $jsonDocument): array;
}
