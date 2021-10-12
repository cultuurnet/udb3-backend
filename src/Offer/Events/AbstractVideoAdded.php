<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Events;

use Broadway\Serializer\Serializable;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\Video;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;

abstract class AbstractVideoAdded implements Serializable
{
    private string $itemId;

    private Video $video;

    final public function __construct(string $itemId, Video $video)
    {
        $this->itemId = $itemId;
        $this->video = $video;
    }

    public function getItemId(): string
    {
        return $this->itemId;
    }

    public function getVideo(): Video
    {
        return $this->video;
    }

    public static function deserialize(array $data): AbstractVideoAdded
    {
        $video = new Video(
            new UUID($data['video']['id']),
            new Url($data['video']['url']),
            new Language($data['video']['language'])
        );

        if (isset($data['video']['copyrightHolder'])) {
            $video = $video->withCopyrightHolder(
                new CopyrightHolder($data['video']['copyrightHolder'])
            );
        }

        return new static(
            $data['item_id'],
            $video
        );
    }

    public function serialize(): array
    {
        $videoAdded = [
            'item_id' => $this->itemId,
            'video' => [
                'id' => $this->video->getId()->toString(),
                'url' => $this->video->getUrl()->toString(),
                'language' => $this->video->getLanguage()->toString(),
            ],
        ];

        if ($this->video->getCopyrightHolder() !== null) {
            $videoAdded['video']['copyrightHolder'] = $this->video->getCopyrightHolder()->toString();
        }

        return $videoAdded;
    }
}
