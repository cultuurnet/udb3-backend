<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\MediaObject;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;

final class Video
{
    private UUID $id;

    private Url $url;

    private ?CopyrightHolder $copyrightHolder = null;

    public function __construct(
        UUID $id,
        Url $url
    ) {
        $this->id = $id;
        $this->url = $url;
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

    public function getCopyrightHolder(): ?CopyrightHolder
    {
        return $this->copyrightHolder;
    }

    public function serialize(): array
    {
        $videoArray = [
            'id' => $this->getId()->toString(),
            'url' => $this->getUrl()->toString(),
        ];

        if ($this->getCopyrightHolder() !== null) {
            $videoArray['copyrightHolder'] = $this->getCopyrightHolder()->toString();
        }

        return $videoArray;
    }
}
