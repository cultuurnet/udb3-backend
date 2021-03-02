<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Validation\ValueObject\Audience;

use CultuurNet\UDB3\Model\Validation\ValueObject\EnumValidator;
use CultuurNet\UDB3\Model\ValueObject\Audience\AudienceType;

class AudienceTypeValidator extends EnumValidator
{
    /**
     * @inheritdoc
     */
    protected function getAllowedValues()
    {
        return AudienceType::getAllowedValues();
    }
}
