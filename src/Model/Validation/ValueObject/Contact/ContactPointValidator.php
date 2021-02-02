<?php

namespace CultuurNet\UDB3\Model\Validation\ValueObject\Contact;

use CultuurNet\UDB3\Model\Validation\ValueObject\Web\EmailAddressesValidator;
use CultuurNet\UDB3\Model\Validation\ValueObject\Web\UrlsValidator;
use Respect\Validation\Rules\Key;
use Respect\Validation\Validator;

class ContactPointValidator extends Validator
{
    public function __construct()
    {
        $rules = [
            new Key('phone', new TelephoneNumbersValidator(), false),
            new Key('email', new EmailAddressesValidator(), false),
            new Key('url', new UrlsValidator(), false),
        ];

        parent::__construct($rules);
    }
}
