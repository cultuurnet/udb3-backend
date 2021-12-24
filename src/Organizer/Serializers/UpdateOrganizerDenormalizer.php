<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Serializers;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Organizer\Commands\UpdateOrganizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class UpdateOrganizerDenormalizer implements DenormalizerInterface
{
    private string $organizerId;

    public function __construct(string $organizerId)
    {
        $this->organizerId = $organizerId;
    }

    public function denormalize($data, $class, $format = null, array $context = [])
    {
        $updateOrganizer = new UpdateOrganizer($this->organizerId);

        if (isset($data['mainImageId'])) {
            $updateOrganizer = $updateOrganizer->withMainImageId(new UUID($data['mainImageId']));
        }

        return $updateOrganizer;
    }

    public function supportsDenormalization($data, $type, $format = null)
    {
        return $type === UpdateOrganizer::class;
    }
}
