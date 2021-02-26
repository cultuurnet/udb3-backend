<?php

namespace CultuurNet\UDB3\Model\Import\Validation\MediaObject;

use CultuurNet\UDB3\Media\MediaManagerInterface;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUIDParser;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\MediaObjectIDParser;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use Respect\Validation\Exceptions\ValidationException;
use Respect\Validation\Rules\AbstractRule;
use ValueObjects\Identity\UUID;

class MediaObjectIDExistsRule extends AbstractRule
{
    /**
     * @var MediaManagerInterface
     */
    private $mediaManager;

    /**
     * @var UUIDParser
     */
    private $mediaIdParser;


    public function __construct(MediaManagerInterface $mediaManager, UUIDParser $mediaIdParser = null)
    {
        if (is_null($mediaIdParser)) {
            $mediaIdParser = new MediaObjectIDParser();
        }

        $this->mediaManager = $mediaManager;
        $this->mediaIdParser = $mediaIdParser;
    }

    /**
     * @param string $input
     * @return bool
     */
    public function validate($input)
    {
        $this->setName($input);

        try {
            $id = $this->mediaIdParser->fromUrl(new Url($input));
            $this->mediaManager->get(new UUID($id->toString()));
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    protected function createException()
    {
        return (new ValidationException())
            ->setTemplate('mediaObject with @id {{name}} does not exist');
    }
}
