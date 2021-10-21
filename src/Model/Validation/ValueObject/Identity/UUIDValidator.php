<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Validation\ValueObject\Identity;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use Respect\Validation\Rules\Regex;
use Respect\Validation\Validator;

final class UUIDValidator extends Validator
{
    public function __construct()
    {
        $rules = [
            new Regex('/' . UUID::BC_REGEX . '/'),
        ];

        parent::__construct($rules);
    }
}
