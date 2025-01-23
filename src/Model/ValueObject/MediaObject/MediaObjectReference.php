<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\MediaObject;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Text\Description;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;

final class MediaObjectReference
{
    private Uuid $mediaObjectId;

    private Description $description;

    private CopyrightHolder $copyrightHolder;

    private Language $language;

    public function __construct(
        Uuid $mediaObjectId,
        Description $description,
        CopyrightHolder $copyrightHolder,
        Language $language
    ) {
        $this->mediaObjectId = $mediaObjectId;
        $this->description = $description;
        $this->copyrightHolder = $copyrightHolder;
        $this->language = $language;
    }

    public function getMediaObjectId(): Uuid
    {
        return $this->mediaObjectId;
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
}
