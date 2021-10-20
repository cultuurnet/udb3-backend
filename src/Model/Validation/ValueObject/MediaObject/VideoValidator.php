<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Validation\ValueObject\MediaObject;

use CultuurNet\UDB3\Model\Validation\ValueObject\Identity\UUIDValidator;
use CultuurNet\UDB3\Model\Validation\ValueObject\Translation\LanguageValidator;
use Respect\Validation\Rules\Key;
use Respect\Validation\Validator;

final class VideoValidator extends Validator
{
    public function __construct()
    {
        $rules = [
            new Key('id', new UUIDValidator(), false),
            new Key('url', new VideoUrlValidator(), true),
            new Key('language', new LanguageValidator(), true),
            new Key('copyrightHolder', new CopyrightHolderValidator(), false),
        ];

        parent::__construct($rules);
    }
}
