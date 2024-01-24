<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\RDF\Editor;

use CultuurNet\UDB3\Model\ValueObject\MediaObject\Video;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\VideoCollection;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\VideoPlatformData;
use EasyRdf\Literal;
use EasyRdf\Resource;

final class VideoEditor
{
    private const TYPE_VIDEO_OBJECT = 'schema:VideoObject';
    private const TYPE_URL = 'schema:URL';

    private const PROPERTY_VIDEO = 'schema:video';
    private const PROPERTY_VIDEO_IDENTIFIER = 'schema:identifier';
    private const PROPERTY_VIDEO_URL = 'schema:url';
    private const PROPERTY_VIDEO_EMBED_URL = 'schema:embedUrl';
    private const PROPERTY_VIDEO_COPYRIGHT_HOLDER = 'schema:copyrightHolder';
    private const PROPERTY_VIDEO_IN_LANGUAGE = 'schema:inLanguage';

    public function setVideos(Resource $resource, VideoCollection $videos): void
    {
        foreach ($videos as $video) {
            $videoResource = $this->createVideoResource($resource, $video);
            $resource->add(self::PROPERTY_VIDEO, $videoResource);
        }
    }

    private function createVideoResource(Resource $resource, Video $video): Resource
    {
        $videoPlatformData = VideoPlatformData::fromVideo($video);

        $videoResource = $resource->getGraph()->newBNode([self::TYPE_VIDEO_OBJECT]);

        $videoResource->set(
            self::PROPERTY_VIDEO_IDENTIFIER,
            new Literal($video->getId())
        );
        $videoResource->set(
            self::PROPERTY_VIDEO_URL,
            new Literal($video->getUrl()->toString(), null, self::TYPE_URL)
        );
        $videoResource->set(
            self::PROPERTY_VIDEO_EMBED_URL,
            new Literal($videoPlatformData['embedUrl'], null, self::TYPE_URL)
        );
        $videoResource->set(
            self::PROPERTY_VIDEO_COPYRIGHT_HOLDER,
            new Literal($video->getCopyrightHolder()->toString())
        );
        $videoResource->set(
            self::PROPERTY_VIDEO_IN_LANGUAGE,
            new Literal($video->getLanguage()->toString())
        );

        return $videoResource;
    }
}
