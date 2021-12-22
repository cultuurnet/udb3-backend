<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Serializers;

use CultuurNet\UDB3\Organizer\Commands\UpdateImage;
use CultuurNet\UDB3\Organizer\Commands\UpdateImages;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class UpdateImagesDenormalizer implements DenormalizerInterface
{
    private UpdateImageDenormalizer $updateImageDenormalizer;

    public function __construct(UpdateImageDenormalizer $updateImageDenormalizer)
    {
        $this->updateImageDenormalizer = $updateImageDenormalizer;
    }

    public function denormalize($data, $class, $format = null, array $context = []): UpdateImages
    {
        $updates = [];
        foreach ($data as $updateImageData) {
            $updates[] = $this->updateImageDenormalizer->denormalize(
                $updateImageData,
                UpdateImage::class
            );
        }
        return new UpdateImages(...$updates);
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === UpdateImages::class;
    }
}
