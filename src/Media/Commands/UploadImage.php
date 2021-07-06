<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media\Commands;

use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Offer\Commands\AuthorizableCommandInterface;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

class UploadImage implements AuthorizableCommandInterface
{
    /**
     * @var UUID
     */
    protected $fileId;

    /**
     * @var Language
     */
    protected $language;

    /**
     * @var StringLiteral
     */
    protected $description;

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
        StringLiteral $description,
        CopyrightHolder $copyrightHolder,
        StringLiteral $filePath,
        Language $language
    ) {
        $this->fileId = $fileId;
        $this->description = $description;
        $this->copyrightHolder = $copyrightHolder;
        $this->mimeType = $mimeType;
        $this->filePath = $filePath;
        $this->language = $language;
    }

    /**
     * @return Language
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @return UUID
     */
    public function getFileId()
    {
        return $this->fileId;
    }

    /**
     * @return StringLiteral
     */
    public function getDescription()
    {
        return $this->description;
    }

    public function getCopyrightHolder(): CopyrightHolder
    {
        return $this->copyrightHolder;
    }

    /**
     * @return MIMEType
     */
    public function getMimeType()
    {
        return $this->mimeType;
    }

    /**
     * @return StringLiteral
     */
    public function getFilePath()
    {
        return $this->filePath;
    }

    public function getItemId(): string
    {
        return (string) $this->getFileId();
    }

    public function getPermission(): Permission
    {
        return Permission::MEDIA_UPLOADEN();
    }
}
