<?php

namespace CultuurNet\UDB3\Model\Validation\ValueObject\Contact;

use CultuurNet\UDB3\Model\Validation\ValueObject\NotEmptyStringValidator;
use Respect\Validation\Rules\ArrayType;
use Respect\Validation\Rules\Each;
use Respect\Validation\Validator;

class TelephoneNumbersValidator extends Validator
{
    public function __construct()
    {
        $rules = [
            new ArrayType(),
            new Each(
                (new NotEmptyStringValidator())->setName('each phone')
            ),
        ];

        parent::__construct($rules);
    }
}
