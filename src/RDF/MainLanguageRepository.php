<?php

namespace CultuurNet\UDB3\RDF;

use CultuurNet\UDB3\Model\ValueObject\Translation\Language;

interface MainLanguageRepository
{
    public function save(string $resourceId, Language $mainLanguage): void;
    public function get(string $resourceId, Language $default = null): ?Language;
}
