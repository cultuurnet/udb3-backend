<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\MediaObject;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;

class MediaObject
{
    /**
     * @var UUID
     */
    private $id;

    /**
     * @var MediaObjectType
     */
    private $type;

    /**
     * @var Url
     */
    private $contentUrl;

    /**
     * @var Url
     */
    private $thumbnailUrl;


    public function __construct(
        UUID $id,
        MediaObjectType $type,
        Url $contentUrl,
        Url $thumbnailUrl
    ) {
        $this->id = $id;
        $this->type = $type;
        $this->contentUrl = $contentUrl;
        $this->thumbnailUrl = $thumbnailUrl;
    }

    /**
     * @return UUID
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return MediaObjectType
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return Url
     */
    public function getContentUrl()
    {
        return $this->contentUrl;
    }

    /**
     * @return Url
     */
    public function getThumbnailUrl()
    {
        return $this->thumbnailUrl;
    }
}
