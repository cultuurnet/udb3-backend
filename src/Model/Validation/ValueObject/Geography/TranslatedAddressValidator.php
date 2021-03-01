<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Validation\ValueObject\Geography;

use CultuurNet\UDB3\Model\Validation\ValueObject\Translation\LanguageValidator;
use Respect\Validation\Rules\ArrayType;
use Respect\Validation\Rules\Each;
use Respect\Validation\Rules\Length;
use Respect\Validation\Validator;

class TranslatedAddressValidator extends Validator
{
    public function __construct()
    {
        $rules = [
            new ArrayType(),
            new Each(
                (new AddressValidator())->setName('address value'),
                new LanguageValidator()
            ),
            new Length(1, null, true),
        ];

        parent::__construct($rules);
    }
}
