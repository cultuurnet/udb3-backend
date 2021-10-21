<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Validation\ValueObject\MediaObject;

use CultuurNet\UDB3\Model\ValueObject\MediaObject\Video;
use Respect\Validation\Rules\Regex;
use Respect\Validation\Validator;

final class VideoUrlValidator extends Validator
{
    public function __construct()
    {
        $rules = [
            new Regex(Video::REGEX),
        ];

        parent::__construct($rules);
    }
}
