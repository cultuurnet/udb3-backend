<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\MediaObject;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Text\Description;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;

final class MediaObjectReference
{
    private UUID $mediaObjectId;

    private ?MediaObject $mediaObject;

    private Description $description;

    private CopyrightHolder $copyrightHolder;

    private Language $language;

    private function __construct(
        UUID $mediaObjectId,
        Description $description,
        CopyrightHolder $copyrightHolder,
        Language $language,
        MediaObject $mediaObject = null
    ) {
        if ($mediaObject) {
            $mediaObjectId = $mediaObject->getId();
        }

        $this->mediaObjectId = $mediaObjectId;
        $this->description = $description;
        $this->copyrightHolder = $copyrightHolder;
        $this->language = $language;
        $this->mediaObject = $mediaObject;
    }

    public function getMediaObjectId(): UUID
    {
        return $this->mediaObjectId;
    }

    public function getEmbeddedMediaObject(): ?MediaObject
    {
        return $this->mediaObject;
    }

    public function getDescription(): Description
    {
        return $this->description;
    }

    public function withDescription(Description $description): MediaObjectReference
    {
        $c = clone $this;
        $c->description = $description;
        return $c;
    }

    public function getCopyrightHolder(): CopyrightHolder
    {
        return $this->copyrightHolder;
    }

    public function withCopyrightHolder(CopyrightHolder $copyrightHolder): MediaObjectReference
    {
        $c = clone $this;
        $c->copyrightHolder = $copyrightHolder;
        return $c;
    }

    public function getLanguage(): Language
    {
        return $this->language;
    }

    public static function createWithMediaObjectId(
        UUID $mediaObjectId,
        Description $description,
        CopyrightHolder $copyrightHolder,
        Language $language
    ): MediaObjectReference {
        return new self(
            $mediaObjectId,
            $description,
            $copyrightHolder,
            $language
        );
    }

    public static function createWithEmbeddedMediaObject(
        MediaObject $mediaObject,
        Description $description,
        CopyrightHolder $copyrightHolder,
        Language $language
    ): MediaObjectReference {
        return new self(
            $mediaObject->getId(),
            $description,
            $copyrightHolder,
            $language,
            $mediaObject
        );
    }
}
