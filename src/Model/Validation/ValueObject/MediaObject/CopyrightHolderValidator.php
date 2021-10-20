<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Validation\ValueObject\MediaObject;

use Respect\Validation\Rules\AllOf;
use Respect\Validation\Rules\Length;
use Respect\Validation\Rules\StringType;
use Respect\Validation\Rules\When;
use Respect\Validation\Validator;

final class CopyrightHolderValidator extends Validator
{
    public function __construct()
    {
        $rules = [
            new StringType(),
            new Length(3)
        ];

        parent::__construct($rules);
    }
}
