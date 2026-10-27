<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media;

use Broadway\EventSourcing\EventSourcedAggregateRoot;
use CultuurNet\UDB3\Media\Events\MediaObjectCreated;
use CultuurNet\UDB3\Media\Properties\Description;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\AiGeneratedImageDescription;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;

class MediaObject extends EventSourcedAggregateRoot
{
    protected MIMEType $mimeType;

    protected Uuid $mediaObjectId;

    protected Description $description;

    protected CopyrightHolder $copyrightHolder;

    protected Url $sourceLocation;

    protected Language $language;

    protected ?bool $isAiGenerated;

    public static function create(
        Uuid $id,
        MIMEType $mimeType,
        Description $description,
        CopyrightHolder $copyrightHolder,
        Url $sourceLocation,
        Language $language,
        ?bool $isAiGenerated = null
    ): MediaObject {
        $mediaObject = new self();
        $mediaObject->apply(
            new MediaObjectCreated(
                $id,
                $mimeType,
                new Description(
                    AiGeneratedImageDescription::apply($description->toString(), $language, $isAiGenerated)
                ),
                $copyrightHolder,
                $sourceLocation,
                $language,
                $isAiGenerated
            )
        );

        return $mediaObject;
    }

    public function getAggregateRootId(): string
    {
        return $this->mediaObjectId->toString();
    }

    protected function applyMediaObjectCreated(MediaObjectCreated $mediaObjectCreated): void
    {
        $this->mediaObjectId = $mediaObjectCreated->getMediaObjectId();
        $this->mimeType = $mediaObjectCreated->getMimeType();
        $this->description = $mediaObjectCreated->getDescription();
        $this->copyrightHolder = $mediaObjectCreated->getCopyrightHolder();
        $this->sourceLocation = $mediaObjectCreated->getSourceLocation();
        $this->language = $mediaObjectCreated->getLanguage();
        $this->isAiGenerated = $mediaObjectCreated->isAiGenerated();
    }

    public function getDescription(): Description
    {
        return $this->description;
    }

    public function getCopyrightHolder(): CopyrightHolder
    {
        return $this->copyrightHolder;
    }

    public function getMediaObjectId(): Uuid
    {
        return $this->mediaObjectId;
    }

    public function getMimeType(): MIMEType
    {
        return $this->mimeType;
    }

    public function getSourceLocation(): Url
    {
        return $this->sourceLocation;
    }

    public function getLanguage(): Language
    {
        return $this->language;
    }

    public function isAiGenerated(): ?bool
    {
        return $this->isAiGenerated;
    }
}
