<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Validation\ValueObject\MediaObject;

use CultuurNet\UDB3\Model\Validation\ValueObject\NotEmptyStringValidator;
use CultuurNet\UDB3\Model\Validation\ValueObject\Translation\LanguageValidator;
use Respect\Validation\Rules\Key;
use Respect\Validation\Rules\Url;
use Respect\Validation\Validator;

class MediaObjectValidator extends Validator
{
    public function __construct()
    {
        $rules = [
            new Key('@id', new MediaObjectIDValidator(), true),
            new Key('description', new NotEmptyStringValidator(), true),
            new Key('copyrightHolder', new NotEmptyStringValidator(), true),
            new Key('inLanguage', new LanguageValidator(), true),
            new Key('@type', new MediaObjectTypeValidator(), false),
            new Key('contentUrl', new Url(), false),
            new Key('thumbnailUrl', new Url(), false),
        ];

        parent::__construct($rules);
    }
}
