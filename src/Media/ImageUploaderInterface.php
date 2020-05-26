<?php

namespace CultuurNet\UDB3\Media;

use CultuurNet\UDB3\Language;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

/**
 * @todo Move to udb3-symfony
 * @see https://jira.uitdatabank.be/browse/III-1513
 */
interface ImageUploaderInterface
{
    public function upload(
        UploadedFile $file,
        StringLiteral $description,
        StringLiteral $copyrightHolder,
        Language $language
    ): UUID;

    /**
     * @return string
     *  path to upload directory
     */
    public function getUploadDirectory();
}
