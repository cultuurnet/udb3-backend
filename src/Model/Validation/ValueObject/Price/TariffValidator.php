<?php

namespace CultuurNet\UDB3\Model\Validation\ValueObject\Price;

use CultuurNet\UDB3\Model\Validation\ValueObject\Text\TranslatedStringValidator;
use Respect\Validation\Rules\Equals;
use Respect\Validation\Rules\FloatType;
use Respect\Validation\Rules\IntType;
use Respect\Validation\Rules\Key;
use Respect\Validation\Rules\OneOf;
use Respect\Validation\Validator;

class TariffValidator extends Validator
{
    public function __construct()
    {
        $rules = [
            new Key('category', new TariffCategoryValidator(), true),
            new Key('name', new TranslatedStringValidator('tariff name'), true),
            new Key(
                'price',
                new OneOf(
                    new IntType(),
                    new FloatType()
                ),
                true
            ),
            new Key('priceCurrency', new Equals('EUR'), true),
        ];

        parent::__construct($rules);
    }
}
