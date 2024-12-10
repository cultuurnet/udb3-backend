<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Serializers;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\Image;
use CultuurNet\UDB3\Model\ValueObject\Text\Description;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Organizer\Commands\AddImage;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class AddImageDenormalizer implements DenormalizerInterface
{
    private string $organizerId;

    public function __construct(string $organizerId)
    {
        $this->organizerId = $organizerId;
    }

    public function denormalize($data, $type, $format = null, array $context = []): AddImage
    {
        return new AddImage(
            $this->organizerId,
            new Image(
                new Uuid($data['id']),
                new Language($data['language']),
                new Description($data['description']),
                new CopyrightHolder($data['copyrightHolder'])
            )
        );
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === AddImage::class;
    }
}
