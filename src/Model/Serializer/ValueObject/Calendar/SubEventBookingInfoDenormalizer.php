<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEventBookingInfo;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class SubEventBookingInfoDenormalizer implements DenormalizerInterface
{
    /**
     * Returns null when there is no url, which only happens for events stored before III-7324,
     * when a subEvent bookingInfo could also have only a phone or email.
     */
    public function denormalize($data, $class, $format = null, array $context = []): ?SubEventBookingInfo
    {
        if (!isset($data['url'])) {
            return null;
        }

        return new SubEventBookingInfo(new Url($data['url']));
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === SubEventBookingInfo::class;
    }
}
