<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Validation\ValueObject\Price;

use CultuurNet\UDB3\Model\Validation\ValueObject\EnumValidator;

class TariffCategoryValidator extends EnumValidator
{
    protected function getAllowedValues()
    {
        return [
            'base',
            'tariff',
        ];
    }
}
