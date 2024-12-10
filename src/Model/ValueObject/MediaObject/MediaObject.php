<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\MediaObject;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;

class MediaObject
{
    private Uuid $id;

    private MediaObjectType $type;

    private Url $contentUrl;

    private Url $thumbnailUrl;

    public function __construct(
        Uuid $id,
        MediaObjectType $type,
        Url $contentUrl,
        Url $thumbnailUrl
    ) {
        $this->id = $id;
        $this->type = $type;
        $this->contentUrl = $contentUrl;
        $this->thumbnailUrl = $thumbnailUrl;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getType(): MediaObjectType
    {
        return $this->type;
    }

    public function getContentUrl(): Url
    {
        return $this->contentUrl;
    }

    public function getThumbnailUrl(): Url
    {
        return $this->thumbnailUrl;
    }
}
