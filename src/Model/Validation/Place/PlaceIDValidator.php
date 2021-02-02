<?php

namespace CultuurNet\UDB3\Model\Validation\Place;

use CultuurNet\UDB3\Model\Place\PlaceIDParser;
use Respect\Validation\Rules\Regex;
use Respect\Validation\Rules\Url;
use Respect\Validation\Validator;

class PlaceIDValidator extends Validator
{
    public function __construct()
    {
        $rules = [
            new Url(),
            new Regex(PlaceIDParser::REGEX),
        ];

        parent::__construct($rules);
    }
}
