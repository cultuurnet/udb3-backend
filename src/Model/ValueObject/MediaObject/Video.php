<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\MediaObject;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Text\Description;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;

final class Video
{
    private UUID $id;

    private Url $url;

    private Description $description;

    private ?CopyrightHolder $copyright = null;

    public function __construct(
        UUID $id,
        Url $url,
        Description $description
    ) {
        $this->id = $id;
        $this->url = $url;
        $this->description = $description;
    }

    public function withCopyrightHolder(CopyrightHolder $copyright): Video
    {
        $clone = clone $this;
        $clone->copyright = $copyright;
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

    public function getDescription(): Description
    {
        return $this->description;
    }

    public function getCopyright(): ?CopyrightHolder
    {
        return $this->copyright;
    }

    public function serialize(): array
    {
        $videoArray = [
            'id' => $this->getId()->toString(),
            'url' => $this->getUrl()->toString(),
            'description' => $this->getDescription()->toString(),
        ];

        if ($this->getCopyright() !== null) {
            $videoArray['copyright'] = $this->getCopyright()->toString();
        }

        return $videoArray;
    }
}
