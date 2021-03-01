<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Validation\ValueObject\MediaObject;

use CultuurNet\UDB3\Model\Validation\ValueObject\EnumValidator;

class MediaObjectTypeValidator extends EnumValidator
{
    /**
     * @inheritdoc
     */
    protected function getAllowedValues()
    {
        return [
            'schema:ImageObject',
            'schema:mediaObject',
        ];
    }
}
