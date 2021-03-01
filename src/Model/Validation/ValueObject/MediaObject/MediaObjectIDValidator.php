<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Validation\ValueObject\MediaObject;

use CultuurNet\UDB3\Model\ValueObject\MediaObject\MediaObjectIDParser;
use Respect\Validation\Rules\Regex;
use Respect\Validation\Rules\Url;
use Respect\Validation\Validator;

class MediaObjectIDValidator extends Validator
{
    public function __construct()
    {
        $rules = [
            new Url(),
            new Regex(MediaObjectIDParser::REGEX),
        ];

        parent::__construct($rules);
    }
}
