<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Serializers;

use CultuurNet\UDB3\Organizer\Commands\UpdateWebsite;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use ValueObjects\Web\Url;

final class UpdateWebsiteDenormalizer implements DenormalizerInterface
{
    private string $organizerId;

    public function __construct(string $organizerId)
    {
        $this->organizerId = $organizerId;
    }

    public function denormalize($data, $type, $format = null, array $context = []): UpdateWebsite
    {
        return new UpdateWebsite($this->organizerId, Url::fromNative($data['url']));
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === UpdateWebsite::class;
    }
}
