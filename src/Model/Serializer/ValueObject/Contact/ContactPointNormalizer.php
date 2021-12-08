<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Contact;

use CultuurNet\UDB3\Model\ValueObject\Contact\ContactPoint;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class ContactPointNormalizer implements NormalizerInterface
{
    /**
     * @param ContactPoint $contactPoint
     */
    public function normalize($contactPoint, $format = null, array $context = []): array
    {
        return [
            'phone' => $contactPoint->getTelephoneNumbers()->toStringArray(),
            'email' => $contactPoint->getEmailAddresses()->toStringArray(),
            'url' => $contactPoint->getUrls()->toStringArray(),
        ];
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return $data === ContactPoint::class;
    }
}
