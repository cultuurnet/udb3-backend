<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Serializers;

use CultuurNet\UDB3\ContactPoint;
use CultuurNet\UDB3\Organizer\Commands\UpdateContactPoint;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class UpdateContactPointDenormalizer implements DenormalizerInterface
{
    private string $organizerId;

    public function __construct(string $organizerId)
    {
        $this->organizerId = $organizerId;
    }

    public function denormalize($data, $type, $format = null, array $context = []): UpdateContactPoint
    {
        return new UpdateContactPoint(
            $this->organizerId,
            new ContactPoint(
                $data['phone'] ?? [],
                $data['email'] ?? [],
                $data['url'] ?? []
            )
        );
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === UpdateContactPoint::class;
    }
}
