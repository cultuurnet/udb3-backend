<?php

namespace CultuurNet\UDB3\Model\Validation\ValueObject\Audience;

use Respect\Validation\Rules\AlwaysValid;
use Respect\Validation\Rules\Regex;
use Respect\Validation\Rules\StringType;
use Respect\Validation\Rules\When;
use Respect\Validation\Validator;

class AgeRangeValidator extends Validator
{
    public function __construct()
    {
        $rules = [
            new StringType(),
            new When(
                new StringType(),
                new Regex('/\\A[\\d]*-[\\d]*\\z/'),
                new AlwaysValid()
            ),
        ];

        parent::__construct($rules);
    }
}
