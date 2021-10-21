<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Validation\ValueObject\MediaObject;

use Respect\Validation\Rules\ArrayType;
use Respect\Validation\Rules\Each;
use Respect\Validation\Validator;

final class VideosValidator extends Validator
{
    public function __construct()
    {
        $rules = [
            new ArrayType(),
            new Each(
                (new VideoValidator())->setName('each video')
            ),
        ];

        parent::__construct($rules);
    }
}
