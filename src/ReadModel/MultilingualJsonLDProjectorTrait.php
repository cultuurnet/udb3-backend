<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\ReadModel;

use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use stdClass;

trait MultilingualJsonLDProjectorTrait
{
    protected function setMainLanguage(stdClass $jsonLd, Language $language): StdClass
    {
        $jsonLd->mainLanguage = $language->getCode();
        return $jsonLd;
    }

    protected function getMainLanguage(stdClass $jsonLd): Language
    {
        if (isset($jsonLd->mainLanguage)) {
            return new Language($jsonLd->mainLanguage);
        } else {
            return new Language('nl');
        }
    }
}
