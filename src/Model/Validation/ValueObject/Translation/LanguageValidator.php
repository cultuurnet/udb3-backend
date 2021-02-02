<?php

namespace CultuurNet\UDB3\Model\Validation\ValueObject\Translation;

use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use Respect\Validation\Rules\Regex;
use Respect\Validation\Validator;

class LanguageValidator extends Validator
{
    public function __construct()
    {
        $rules = [
            new Regex(Language::REGEX),
        ];

        parent::__construct($rules);
    }
}
