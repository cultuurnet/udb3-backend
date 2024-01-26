<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\MediaObject;

final class VideoPlatform
{
    private string $embed;
    private string $name;
    private string $videoId;
    private string $embedUrl;

    public function __construct(
        string $embed,
        string $name,
        string $videoId,
        string $embedUrl
    ) {
        $this->embed = $embed;
        $this->name = $name;
        $this->videoId = $videoId;
        $this->embedUrl = $embedUrl;
    }

    public function getEmbed(): string
    {
        return $this->embed;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getVideoId(): string
    {
        return $this->videoId;
    }

    public function getEmbedUrl(): string
    {
        return $this->embedUrl;
    }
}
