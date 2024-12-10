<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Media\Commands\UploadImage;
use CultuurNet\UDB3\Media\Properties\Description;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;

interface MediaManagerInterface extends CommandHandler
{
    public function get(Uuid $id): MediaObject;

    public function getImage(Uuid $imageId): Image;

    public function handleUploadImage(UploadImage $uploadImage): void;

    public function create(
        Uuid $id,
        MIMEType $mimeType,
        Description $description,
        CopyrightHolder $copyrightHolder,
        Url $sourceLocation,
        Language $language
    ): MediaObject;
}
