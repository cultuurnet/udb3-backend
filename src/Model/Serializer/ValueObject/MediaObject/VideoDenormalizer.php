<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\MediaObject;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\Video;
use CultuurNet\UDB3\Model\ValueObject\Text\Description;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use Ramsey\Uuid\UuidFactoryInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class VideoDenormalizer implements DenormalizerInterface
{
    private UuidFactoryInterface $uuidFactory;

    public function __construct(UuidFactoryInterface $uuidFactory)
    {
        $this->uuidFactory = $uuidFactory;
    }

    public function denormalize($data, $class, $format = null, array $context = []): Video
    {
        if (!isset($data['id'])) {
            $data['id'] = $this->uuidFactory->uuid4()->toString();
        }

        $video = new Video(
            new UUID($data['id']),
            new Url($data['url'])
        );

        if (isset($data['copyright'])) {
            $video = $video->withCopyrightHolder(new CopyrightHolder($data['copyrightHolder']));
        }

        return $video;
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === Video::class;
    }
}
