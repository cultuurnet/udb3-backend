<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media\Commands;

use CultuurNet\UDB3\Media\Properties\Description;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;

final class UploadImage
{
    private Uuid $fileId;

    private Language $language;

    private Description $description;

    private CopyrightHolder $copyrightHolder;

    private MIMEType $mimeType;

    private string $filePath;

    private ?bool $isAiGenerated;

    public function __construct(
        Uuid $fileId,
        MIMEType $mimeType,
        Description $description,
        CopyrightHolder $copyrightHolder,
        string $filePath,
        Language $language,
        ?bool $isAiGenerated = null
    ) {
        $this->fileId = $fileId;
        $this->description = $description;
        $this->copyrightHolder = $copyrightHolder;
        $this->mimeType = $mimeType;
        $this->filePath = $filePath;
        $this->language = $language;
        $this->isAiGenerated = $isAiGenerated;
    }

    public function getLanguage(): Language
    {
        return $this->language;
    }

    public function getFileId(): Uuid
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

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    public function isAiGenerated(): ?bool
    {
        return $this->isAiGenerated;
    }
}
