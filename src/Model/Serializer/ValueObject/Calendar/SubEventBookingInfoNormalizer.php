<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEventBookingInfo;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class SubEventBookingInfoNormalizer implements NormalizerInterface
{
    /**
     * @param SubEventBookingInfo $bookingInfo
     */
    public function normalize($bookingInfo, $format = null, array $context = []): array
    {
        return [
            'url' => $bookingInfo->getUrl()->toString(),
        ];
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return $data instanceof SubEventBookingInfo;
    }
}
