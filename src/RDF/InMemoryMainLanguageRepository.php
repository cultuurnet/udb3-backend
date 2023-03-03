<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\RDF;

use CultuurNet\UDB3\Model\ValueObject\Translation\Language;

final class InMemoryMainLanguageRepository implements MainLanguageRepository
{
    private array $languages;

    public function save(string $resourceId, Language $mainLanguage): void
    {
        $this->languages[$resourceId] = $mainLanguage;
    }

    public function get(string $resourceId, Language $default = null): ?Language
    {
        return $this->languages[$resourceId] ?? $default;
    }
}
