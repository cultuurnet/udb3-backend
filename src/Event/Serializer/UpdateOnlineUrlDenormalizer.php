<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Serializer;

use CultuurNet\UDB3\Event\Commands\UpdateOnlineUrl;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class UpdateOnlineUrlDenormalizer implements DenormalizerInterface
{
    private string $eventId;

    public function __construct(string $eventId)
    {
        $this->eventId = $eventId;
    }

    public function denormalize($data, $class, $format = null, array $context = []): UpdateOnlineUrl
    {
        return new UpdateOnlineUrl(
            $this->eventId,
            new Url($data['onlineUrl'])
        );
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === UpdateOnlineUrl::class;
    }
}
