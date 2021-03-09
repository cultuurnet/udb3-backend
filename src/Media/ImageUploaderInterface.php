<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media;

use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

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
