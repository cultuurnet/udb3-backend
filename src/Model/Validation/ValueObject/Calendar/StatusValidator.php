<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Validation\ValueObject\Calendar;

use CultuurNet\UDB3\Model\Validation\ValueObject\Text\TranslatedStringValidator;
use Respect\Validation\Rules\ArrayType;
use Respect\Validation\Rules\Key;
use Respect\Validation\Validator;

final class StatusValidator extends Validator
{
    public function __construct()
    {
        $rules = [
            new ArrayType(),
            new Key('type', new StatusTypeValidator(), false),
            new Key('reason', new TranslatedStringValidator('reason'), false),
        ];

        parent::__construct($rules);
    }
}
