<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media;

use CultuurNet\UDB3\Language;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

interface ImageUploaderInterface
{
    public function upload(
        UploadedFile $file,
        StringLiteral $description,
        StringLiteral $copyrightHolder,
        Language $language
    ): UUID;

    public function getUploadDirectory(): string;
}
