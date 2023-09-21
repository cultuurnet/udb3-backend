<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media\Commands;

use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Media\Properties\Description;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\StringLiteral;

class UploadImage
{
    protected UUID $fileId;

    /**
     * @var Language
     */
    protected $language;

    protected Description $description;

    /**
     * @var CopyrightHolder
     */
    protected $copyrightHolder;

    /**
     * @var MIMEType
     */
    protected $mimeType;

    /**
     * @var StringLiteral
     */
    protected $filePath;

    public function __construct(
        UUID $fileId,
        MIMEType $mimeType,
        Description $description,
        CopyrightHolder $copyrightHolder,
        StringLiteral$filePath,
        Language $language
    ) {
        $this->fileId = $fileId;
        $this->description = $description;
        $this->copyrightHolder = $copyrightHolder;
        $this->mimeType = $mimeType;
        $this->filePath = $filePath;
        $this->language = $language;
    }

    public function getLanguage(): Language
    {
        return $this->language;
    }

    public function getFileId(): UUID
    {
        return $this->fileId;
    }

    public function getDescription(): Description
    {
        return $this->description;
    }

    public function getCopyrightHolder(): CopyrightHolder
    {
        return $this->copyrightHolder;
    }

    public function getMimeType(): MIMEType
    {
        return $this->mimeType;
    }

    public function getFilePath(): StringLiteral
    {
        return $this->filePath;
    }
}
