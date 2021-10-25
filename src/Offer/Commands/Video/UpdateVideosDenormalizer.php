<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands\Video;

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class UpdateVideosDenormalizer implements DenormalizerInterface
{
    private UpdateVideoDenormalizer $updateVideoDenormalizer;

    public function __construct(UpdateVideoDenormalizer $updateVideoDenormalizer)
    {
        $this->updateVideoDenormalizer = $updateVideoDenormalizer;
    }

    public function denormalize($data, $class, $format = null, array $context = []): UpdateVideos
    {
        $updates = [];
        foreach ($data as $updateVideoData) {
            $updates[] = $this->updateVideoDenormalizer->denormalize(
                $updateVideoData,
                UpdateVideo::class
            );
        }
        return new UpdateVideos(...$updates);
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === UpdateVideos::class;
    }
}
