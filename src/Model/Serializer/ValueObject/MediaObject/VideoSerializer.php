<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\MediaObject;

use CultuurNet\UDB3\Model\ValueObject\MediaObject\Video;

final class VideoSerializer
{


    public function serialize(Video $video): array
    {
        $videoArray = [
            'id' => $video->getId()->toString(),
            'url' => $video->getUrl()->toString(),
        ];

        if ($video->getCopyrightHolder() !== null) {
            $videoArray['copyrightHolder'] = $video->getCopyrightHolder()->toString();
        }

        return $videoArray;
    }
}