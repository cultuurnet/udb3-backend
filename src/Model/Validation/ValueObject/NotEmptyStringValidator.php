<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Validation\ValueObject;

use Respect\Validation\Rules\NotEmpty;
use Respect\Validation\Rules\StringType;
use Respect\Validation\Validator;

class NotEmptyStringValidator extends Validator
{
    public function __construct()
    {
        $rules = [
            new StringType(),
            new NotEmpty(),
        ];

        parent::__construct($rules);
    }
}
