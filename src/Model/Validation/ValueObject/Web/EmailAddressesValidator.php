<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Validation\ValueObject\Web;

use Respect\Validation\Rules\ArrayType;
use Respect\Validation\Rules\Each;
use Respect\Validation\Rules\Email;
use Respect\Validation\Validator;

class EmailAddressesValidator extends Validator
{
    public function __construct()
    {
        $rules = [
            new ArrayType(),
            new Each(
                (new Email())->setName('each email')
            ),
        ];

        parent::__construct($rules);
    }
}
