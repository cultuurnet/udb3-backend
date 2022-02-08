<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Media\Commands\UploadImage;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use ValueObjects\StringLiteral\StringLiteral;

interface MediaManagerInterface extends CommandHandler
{
    public function get(UUID $id): MediaObject;

    public function getImage(UUID $imageId): Image;

    public function handleUploadImage(UploadImage $uploadImage): void;

    public function create(
        UUID $id,
        MIMEType $mimeType,
        StringLiteral $description,
        CopyrightHolder $copyrightHolder,
        Url $sourceLocation,
        Language $language
    ): MediaObject;
}
