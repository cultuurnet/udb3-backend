<?php

namespace CultuurNet\UDB3\Model\ValueObject\Web;

use CultuurNet\UDB3\Model\ValueObject\Translation\TranslatedValueObject;

class TranslatedWebsiteLabel extends TranslatedValueObject
{
    /**
     * @inheritdoc
     */
    protected function getValueObjectClassName()
    {
        return WebsiteLabel::class;
    }
}
