<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Validation\Organizer;

use CultuurNet\UDB3\Model\Organizer\OrganizerIDParser;
use Respect\Validation\Rules\Regex;
use Respect\Validation\Rules\Url;
use Respect\Validation\Validator;

class OrganizerIDValidator extends Validator
{
    public function __construct()
    {
        $rules = [
            new Url(),
            new Regex(OrganizerIDParser::REGEX),
        ];

        parent::__construct($rules);
    }
}
