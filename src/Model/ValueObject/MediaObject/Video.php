<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\MediaObject;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Text\Description;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;

class Video
{
    private UUID $videoId;

    private Url $url;

    private Description $description;

    private ?CopyrightHolder $copyright = null;

    public function __construct(
        UUID $videoId,
        Url $url,
        Description $description
    ) {
        $this->videoId = $videoId;
        $this->url = $url;
        $this->description = $description;
    }

    public function withCopyrightHolder(CopyrightHolder $copyright): Video
    {
        $clone = clone $this;
        $clone->copyright = $copyright;
        return $clone;
    }

    public function getVideoId(): UUID
    {
        return $this->videoId;
    }

    public function getUrl(): Url
    {
        return $this->url;
    }

    public function getDescription(): Description
    {
        return $this->description;
    }

    public function getCopyright(): ?CopyrightHolder
    {
        return $this->copyright;
    }
}
