<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer;

use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddresses;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class ContributorDenormalizer implements DenormalizerInterface
{
    public function denormalize($data, $class, $format = null, array $context = []): EmailAddresses
    {
        return EmailAddresses::fromArray(
            array_map(
                fn (string $email) => new EmailAddress($email),
                $data
            )
        );
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === EmailAddresses::class;
    }
}
