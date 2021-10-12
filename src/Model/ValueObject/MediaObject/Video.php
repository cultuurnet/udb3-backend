<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\MediaObject;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;

final class Video
{
    private UUID $id;

    private Url $url;

    private Language $language;

    private ?CopyrightHolder $copyrightHolder = null;

    public function __construct(
        UUID $id,
        Url $url,
        Language $language
    ) {
        $this->id = $id;
        $this->url = $url;
        $this->language = $language;
    }

    public function withCopyrightHolder(CopyrightHolder $copyright): Video
    {
        $clone = clone $this;
        $clone->copyrightHolder = $copyright;
        return $clone;
    }

    public function getId(): UUID
    {
        return $this->id;
    }

    public function getUrl(): Url
    {
        return $this->url;
    }

    public function getLanguage(): Language
    {
        return $this->language;
    }

    public function getCopyrightHolder(): ?CopyrightHolder
    {
        return $this->copyrightHolder;
    }
}
