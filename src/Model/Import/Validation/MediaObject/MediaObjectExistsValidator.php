<?php

namespace CultuurNet\UDB3\Model\Import\Validation\MediaObject;

use CultuurNet\UDB3\Media\MediaManagerInterface;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUIDParser;
use Respect\Validation\Rules\Key;
use Respect\Validation\Validator;

class MediaObjectExistsValidator extends Validator
{
    /**
     * @param MediaManagerInterface $mediaManager
     * @param UUIDParser|null $mediaIdParser
     */
    public function __construct(MediaManagerInterface $mediaManager, UUIDParser $mediaIdParser = null)
    {
        // Only check that the id exists if it is set. If it's missing, that
        // error will be reported by a different validator.
        $rules = [
            new Key('@id', new MediaObjectIDExistsRule($mediaManager, $mediaIdParser), false),
        ];

        parent::__construct($rules);
    }
}
