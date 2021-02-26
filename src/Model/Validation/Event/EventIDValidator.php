<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Validation\Event;

use CultuurNet\UDB3\Model\Event\EventIDParser;
use Respect\Validation\Rules\Regex;
use Respect\Validation\Rules\Url;
use Respect\Validation\Validator;

class EventIDValidator extends Validator
{
    public function __construct()
    {
        $rules = [
            new Url(),
            new Regex(EventIDParser::REGEX),
        ];

        parent::__construct($rules);
    }
}
