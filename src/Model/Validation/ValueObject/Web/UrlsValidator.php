<?php

namespace CultuurNet\UDB3\Model\Validation\ValueObject\Web;

use Respect\Validation\Rules\ArrayType;
use Respect\Validation\Rules\Each;
use Respect\Validation\Rules\Url;
use Respect\Validation\Validator;

class UrlsValidator extends Validator
{
    public function __construct()
    {
        $rules = [
            new ArrayType(),
            new Each(
                (new Url())->setName('each url')
            ),
        ];

        parent::__construct($rules);
    }
}
