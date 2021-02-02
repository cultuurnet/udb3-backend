<?php

namespace CultuurNet\UDB3\Model\Validation\Organizer;

use Respect\Validation\Rules\Key;
use Respect\Validation\Validator;

class OrganizerReferenceValidator extends Validator
{
    public function __construct()
    {
        // @id is not mandatory because there is such a thing as "dummy organizers".
        // We do not support dummy organizers at the time of writing, but we do
        // take them into account so the whole event/place is not invalidated if
        // it contains one.
        $rules = [
            (new Key('@id', new OrganizerIDValidator(), false))
                ->setName('organizer @id'),
        ];

        parent::__construct($rules);
    }
}
