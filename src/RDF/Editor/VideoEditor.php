<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\RDF\Editor;

use CultuurNet\UDB3\Model\Serializer\ValueObject\MediaObject\VideoNormalizer;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\Video;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\VideoCollection;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\VideoPlatformFactory;
use CultuurNet\UDB3\RDF\NodeUri\ResourceFactory\RdfResourceFactory;
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
    private RdfResourceFactory $rdfResourceFactory;
    private VideoNormalizer $videoNormalizer;

    public function __construct(RdfResourceFactory $rdfResourceFactory, VideoNormalizer $videoNormalizer)
    {
        $this->rdfResourceFactory = $rdfResourceFactory;
        $this->videoNormalizer = $videoNormalizer;
    }

    public function setVideos(Resource $resource, VideoCollection $videos): void
    {
        foreach ($videos as $video) {
            $videoResource = $this->createVideoResource($resource, $video);
            $resource->add(self::PROPERTY_VIDEO, $videoResource);
        }
    }

    private function createVideoResource(Resource $resource, Video $video): Resource
    {
        $videoPlatform = VideoPlatformFactory::fromVideo($video);

        $videoResource = $this->rdfResourceFactory->create(
            $resource,
            self::TYPE_VIDEO_OBJECT,
            $this->videoNormalizer->normalize($video)
        );

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
            new Literal($videoPlatform->getEmbedUrl(), null, self::TYPE_URL)
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
