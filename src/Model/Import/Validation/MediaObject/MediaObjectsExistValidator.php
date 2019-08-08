<?php

namespace CultuurNet\UDB3\Model\Import\Validation\MediaObject;

use CultuurNet\UDB3\Media\MediaManagerInterface;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUIDParser;
use Respect\Validation\Rules\AlwaysValid;
use Respect\Validation\Rules\ArrayType;
use Respect\Validation\Rules\Each;
use Respect\Validation\Rules\When;
use Respect\Validation\Validator;

class MediaObjectsExistValidator extends Validator
{
    /**
     * @param MediaManagerInterface $mediaManager
     * @param UUIDParser|null $mediaIdParser
     */
    public function __construct(MediaManagerInterface $mediaManager, UUIDParser $mediaIdParser = null)
    {
        // Only check that the mediaObjects exist if they are in the expected format.
        // Any other errors will be reported by the validators in udb3-models.
        $rules = [
            new When(
                new ArrayType(),
                (new Each(
                    new When(
                        new ArrayType(),
                        new MediaObjectExistsValidator($mediaManager, $mediaIdParser),
                        new AlwaysValid()
                    )
                ))->setName('mediaObject'),
                new AlwaysValid()
            )
        ];

        parent::__construct($rules);
    }
}
