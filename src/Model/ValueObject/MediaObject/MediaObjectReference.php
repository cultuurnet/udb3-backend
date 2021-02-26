<?php

namespace CultuurNet\UDB3\Model\ValueObject\MediaObject;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Text\Description;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;

final class MediaObjectReference
{
    /**
     * @var UUID
     */
    private $mediaObjectId;

    /**
     * @var MediaObject|null
     */
    private $mediaObject;

    /**
     * @var Description
     */
    private $description;

    /**
     * @var CopyrightHolder
     */
    private $copyrightHolder;

    /**
     * @var Language
     */
    private $language;


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

    /**
     * @return UUID
     */
    public function getMediaObjectId()
    {
        return $this->mediaObjectId;
    }

    /**
     * @return MediaObject|null
     */
    public function getEmbeddedMediaObject()
    {
        return $this->mediaObject;
    }

    /**
     * @return Description
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return MediaObjectReference
     */
    public function withDescription(Description $description)
    {
        $c = clone $this;
        $c->description = $description;
        return $c;
    }

    /**
     * @return CopyrightHolder
     */
    public function getCopyrightHolder()
    {
        return $this->copyrightHolder;
    }

    /**
     * @return MediaObjectReference
     */
    public function withCopyrightHolder(CopyrightHolder $copyrightHolder)
    {
        $c = clone $this;
        $c->copyrightHolder = $copyrightHolder;
        return $c;
    }

    /**
     * @return Language
     */
    public function getLanguage()
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
