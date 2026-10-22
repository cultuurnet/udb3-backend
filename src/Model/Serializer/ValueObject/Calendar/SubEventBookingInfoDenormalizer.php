<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEventBookingInfo;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class SubEventBookingInfoDenormalizer implements DenormalizerInterface
{
    /**
     * Events stored between III-7040 and III-7324 (February to July 2026) can still have a subEvent bookingInfo.
     * That copy of the event's bookingInfo can have only a phone or email, which returns null here, or a url,
     * which comes back as the reservation link of that date.
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
