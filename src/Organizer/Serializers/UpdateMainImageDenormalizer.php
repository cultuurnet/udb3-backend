<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Serializers;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Organizer\Commands\UpdateMainImage;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class UpdateMainImageDenormalizer implements DenormalizerInterface
{
    private string $organizerId;

    public function __construct(string $organizerId)
    {
        $this->organizerId = $organizerId;
    }

    public function denormalize($data, $class, $format = null, array $context = []): UpdateMainImage
    {
        return new UpdateMainImage($this->organizerId, new Uuid($data['imageId']));
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === UpdateMainImage::class;
    }
}
