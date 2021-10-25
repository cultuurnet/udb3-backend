<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Web;

use CultuurNet\UDB3\Model\ValueObject\Translation\TranslatedValueObject;

class TranslatedWebsiteLabel extends TranslatedValueObject
{
    protected function getValueObjectClassName(): string
    {
        return WebsiteLabel::class;
    }
}
