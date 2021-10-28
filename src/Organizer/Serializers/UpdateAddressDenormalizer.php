<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Serializers;

use CultuurNet\UDB3\Model\ValueObject\Geography\Address;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Locality;
use CultuurNet\UDB3\Model\ValueObject\Geography\PostalCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Street;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Organizer\Commands\UpdateAddress;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class UpdateAddressDenormalizer implements DenormalizerInterface
{
    private string $organizerId;

    private Language $language;

    public function __construct(string $organizerId, Language $language)
    {
        $this->organizerId = $organizerId;
        $this->language = $language;
    }

    public function denormalize($data, $class, $format = null, array $context = []): UpdateAddress
    {
        return new UpdateAddress(
            $this->organizerId,
            new Address(
                new Street($data['streetAddress']),
                new PostalCode($data['postalCode']),
                new Locality($data['addressLocality']),
                new CountryCode($data['addressCountry'])
            ),
            $this->language
        );
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === UpdateAddress::class;
    }
}
