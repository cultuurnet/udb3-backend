<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Serializers;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\Text\Description;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Organizer\Commands\UpdateImage;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class UpdateImageDenormalizer implements DenormalizerInterface
{
    private string $organizerId;

    public function __construct(string $organizerId)
    {
        $this->organizerId = $organizerId;
    }

    public function denormalize($data, $type, $format = null, array $context = []): UpdateImage
    {
        $updateImage = new UpdateImage(
            $this->organizerId,
            new Uuid($data['id'])
        );

        if (isset($data['language'])) {
            $updateImage = $updateImage->withLanguage(new Language($data['language']));
        }

        if (isset($data['description'])) {
            $updateImage = $updateImage->withDescription(new Description($data['description']));
        }

        if (isset($data['copyrightHolder'])) {
            $updateImage = $updateImage->withCopyrightHolder(new CopyrightHolder($data['copyrightHolder']));
        }

        return $updateImage;
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === UpdateImage::class;
    }
}
