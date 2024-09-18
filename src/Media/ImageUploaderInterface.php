<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media;

use CultuurNet\UDB3\Media\Properties\Description;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use Psr\Http\Message\UploadedFileInterface;

interface ImageUploaderInterface
{
    public function upload(
        UploadedFileInterface $file,
        Description $description,
        CopyrightHolder $copyrightHolder,
        Language $language
    ): UUID;

    public function getUploadDirectory(): string;
}
