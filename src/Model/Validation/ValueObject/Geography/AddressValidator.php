<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Validation\ValueObject\Geography;

use CultuurNet\UDB3\Model\Validation\ValueObject\NotEmptyStringValidator;
use Respect\Validation\Rules\CountryCode;
use Respect\Validation\Rules\Key;
use Respect\Validation\Validator;

class AddressValidator extends Validator
{
    public function __construct()
    {
        $rules = [
            new Key('streetAddress', new NotEmptyStringValidator(), true),
            new Key('postalCode', new NotEmptyStringValidator(), true),
            new Key('addressLocality', new NotEmptyStringValidator(), true),
            new Key('addressCountry', new CountryCode(), true),
        ];

        parent::__construct($rules);
    }
}
