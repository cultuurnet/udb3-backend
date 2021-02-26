<?php

namespace CultuurNet\UDB3\Media;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Media\Commands\UploadImage;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\Url;

interface MediaManagerInterface extends CommandHandler
{
    /**
     * @throws MediaObjectNotFoundException
     * @return MediaObject
     */
    public function get(UUID $id);

    public function getImage(UUID $imageId): Image;


    public function handleUploadImage(UploadImage $uploadImage);

    /**
     *
     * @return MediaObject
     */
    public function create(
        UUID $id,
        MIMEType $mimeType,
        StringLiteral $description,
        StringLiteral $copyrightHolder,
        Url $sourceLocation,
        Language $language
    );
}
