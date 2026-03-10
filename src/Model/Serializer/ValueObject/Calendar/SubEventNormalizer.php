<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar;

use CultuurNet\UDB3\Model\Serializer\ValueObject\Contact\BookingInfoNormalizer;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvent;
use DateTimeInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class SubEventNormalizer implements NormalizerInterface
{
    /**
     * @param SubEvent $subEvent
     */
    public function normalize($subEvent, $format = null, array $context = []): array
    {
        $normalized = [
            'startDate' => $subEvent->getDateRange()->getFrom()->format(DateTimeInterface::ATOM),
            'endDate' => $subEvent->getDateRange()->getTo()->format(DateTimeInterface::ATOM),
            'status' => (new StatusNormalizer())->normalize($subEvent->getStatus()),
            'bookingAvailability' => (new BookingAvailabilityNormalizer())->normalize($subEvent->getBookingAvailability()),
        ];

        $bookingInfo = (new BookingInfoNormalizer())->normalize($subEvent->getBookingInfo());
        if (!empty($bookingInfo)) {
            $normalized['bookingInfo'] = $bookingInfo;
        }

        return $this->addChildcareTimeRangeFields($subEvent, $normalized);
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return $data === SubEvent::class;
    }

    private function addChildcareTimeRangeFields(SubEvent $subEvent, array $normalized): array
    {
        $childcareTimeRange = $subEvent->getChildcareTimeRange();
        if ($childcareTimeRange !== null) {
            if ($childcareTimeRange->getStart() !== null) {
                $normalized['childcareStartTime'] = $childcareTimeRange->getStart();
            }
            if ($childcareTimeRange->getEnd() !== null) {
                $normalized['childcareEndTime'] = $childcareTimeRange->getEnd();
            }
        }
        return $normalized;
    }
}
