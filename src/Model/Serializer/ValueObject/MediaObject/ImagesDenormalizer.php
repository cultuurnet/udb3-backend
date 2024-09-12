<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\MediaObject;

use CultuurNet\UDB3\Model\ValueObject\MediaObject\Image;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\Images;
use Symfony\Component\Serializer\Exception\UnsupportedException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class ImagesDenormalizer implements DenormalizerInterface
{
    private DenormalizerInterface $imageDenormalizer;

    public function __construct(DenormalizerInterface $imageDenormalizer = null)
    {
        if (!$imageDenormalizer) {
            $imageDenormalizer = new ImageDenormalizer();
        }
        $this->imageDenormalizer = $imageDenormalizer;
    }

    /**
     * @inheritdoc
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        if (!$this->supportsDenormalization($data, $class, $format)) {
            throw new UnsupportedException("ImagesDenormalizer does not support {$class}.");
        }

        if (!is_array($data)) {
            throw new UnsupportedException('Images data should be an array.');
        }

        $images = array_map(
            fn ($imageData) => $this->imageDenormalizer->denormalize($imageData, Image::class),
            $data
        );
        return new Images(...$images);
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === Images::class;
    }
}
