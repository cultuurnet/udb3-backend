<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Serializer;

use CultuurNet\UDB3\Event\Commands\UpdateAudience;
use CultuurNet\UDB3\Event\ValueObjects\Audience;
use CultuurNet\UDB3\Event\ValueObjects\AudienceType;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class UpdateAudienceDenormalizer implements DenormalizerInterface
{
    private string $eventId;

    public function __construct(string $eventId)
    {
        $this->eventId = $eventId;
    }

    public function denormalize($data, $class, $format = null, array $context = [])
    {
        $audienceType = AudienceType::fromNative($data['audienceType']);
        return new UpdateAudience($this->eventId, new Audience($audienceType));
    }

    public function supportsDenormalization($data, $type, $format = null)
    {
        return $type === UpdateAudience::class;
    }
}
