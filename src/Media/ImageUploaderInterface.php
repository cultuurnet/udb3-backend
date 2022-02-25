<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media;

use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use CultuurNet\UDB3\StringLiteral;

interface ImageUploaderInterface
{
    public function upload(
        UploadedFile $file,
        StringLiteral $description,
        CopyrightHolder $copyrightHolder,
        Language $language
    ): UUID;

    public function getUploadDirectory(): string;
}
