<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Serializer;

use CultuurNet\UDB3\Event\Commands\UpdateAudience;
use CultuurNet\UDB3\Model\ValueObject\Audience\AudienceType;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class UpdateAudienceDenormalizer implements DenormalizerInterface
{
    private string $eventId;

    public function __construct(string $eventId)
    {
        $this->eventId = $eventId;
    }

    public function denormalize($data, $class, $format = null, array $context = []): UpdateAudience
    {
        return new UpdateAudience(
            $this->eventId,
            new AudienceType($data['audienceType'])
        );
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === UpdateAudience::class;
    }
}
