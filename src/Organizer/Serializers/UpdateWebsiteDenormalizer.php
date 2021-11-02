<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Serializers;

use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Organizer\Commands\UpdateWebsite;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class UpdateWebsiteDenormalizer implements DenormalizerInterface
{
    private string $organizerId;

    public function __construct(string $organizerId)
    {
        $this->organizerId = $organizerId;
    }

    public function denormalize($data, $type, $format = null, array $context = []): UpdateWebsite
    {
        return new UpdateWebsite($this->organizerId, new Url($data['url']));
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === UpdateWebsite::class;
    }
}
