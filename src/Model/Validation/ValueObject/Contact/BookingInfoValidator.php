<?php

namespace CultuurNet\UDB3\Model\Validation\ValueObject\Contact;

use CultuurNet\UDB3\Model\Validation\ValueObject\NotEmptyStringValidator;
use CultuurNet\UDB3\Model\Validation\ValueObject\Text\TranslatedStringValidator;
use Respect\Validation\Rules\AllOf;
use Respect\Validation\Rules\AlwaysValid;
use Respect\Validation\Rules\Date;
use Respect\Validation\Rules\Email;
use Respect\Validation\Rules\Key;
use Respect\Validation\Rules\KeyValue;
use Respect\Validation\Rules\Url;
use Respect\Validation\Rules\When;
use Respect\Validation\Validator;

class BookingInfoValidator extends Validator
{
    public function __construct()
    {
        $rules = [
            new Key('phone', new NotEmptyStringValidator(), false),
            new Key('email', new Email(), false),
            new Key('url', new Url(), false),
            new When(
                new Key('url', new AlwaysValid(), true),
                new Key('urlLabel', new TranslatedStringValidator('urlLabel'), true),
                new AlwaysValid()
            ),
            new Key('availabilityStarts', new Date(\DATE_ATOM), false),
            new Key('availabilityEnds', new Date(\DATE_ATOM), false),
            new When(
                new AllOf(
                    new Key('availabilityStarts', new Date(\DATE_ATOM), true),
                    new Key('availabilityEnds', new Date(\DATE_ATOM), true)
                ),
                new KeyValue('availabilityEnds', 'min', 'availabilityStarts'),
                new AlwaysValid()
            ),
        ];

        parent::__construct($rules);
    }
}
