<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailability;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailabilityType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Calendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\CalendarWithDateRange;
use CultuurNet\UDB3\Model\ValueObject\Calendar\CalendarWithOpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\CalendarWithSubEvents;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Status;
use CultuurNet\UDB3\Model\ValueObject\Calendar\StatusType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvents;
use DateTimeInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class CalendarNormalizer implements NormalizerInterface
{
    /**
     * @param Calendar $calendar
     */
    public function normalize($calendar, $format = null, array $context = []): array
    {
        $data = [];

        $data['calendarType'] = $calendar->getType()->toString();

        if ($calendar instanceof CalendarWithDateRange) {
            $data['startDate'] = $calendar->getStartDate()->format(DateTimeInterface::ATOM);
            $data['endDate'] = $calendar->getEndDate()->format(DateTimeInterface::ATOM);
        }

        $data['status'] = (new StatusNormalizer())->normalize($this->determineCorrectTopStatusForProjection($calendar));

        $data['bookingAvailability'] = (new BookingAvailabilityNormalizer())->normalize($this->determineCorrectTopBookingAvailabilityForProjection($calendar));

        if ($calendar instanceof CalendarWithSubEvents) {
            $subEvents = array_values($calendar->getSubEvents()->toArray());
            if (!empty($subEvents)) {
                $data['subEvent'] = [];
                foreach ($subEvents as $id => $subEvent) {
                    $jsonLdSubEvent = (new SubEventNormalizer())->normalize($subEvent);
                    $jsonLdSubEvent['@type'] = 'Event';

                    $data['subEvent'][] = ['id' => $id] + $jsonLdSubEvent;
                }
            }
        }

        if ($calendar instanceof CalendarWithOpeningHours) {
            $openingHours = $calendar->getOpeningHours()->toArray();
            if (!empty($openingHours)) {
                $data['openingHours'] = [];
                foreach ($openingHours as $openingHour) {
                    $data['openingHours'][] = (new OpeningHourNormalizer())->normalize($openingHour);
                }
            }
        }

        return $data;
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return $data === Calendar::class;
    }

    private function deriveStatusTypeFromSubEvents(SubEvents $subEvents): StatusType
    {
        $temporarilyUnavailablePresent = false;
        $unavailablePresent = false;

        foreach ($subEvents as $subEvent) {
            if ($subEvent->getStatus()->getType()->sameAs(StatusType::Available())) {
                return StatusType::Available();
            }

            if ($subEvent->getStatus()->getType()->sameAs(StatusType::TemporarilyUnavailable())) {
                $temporarilyUnavailablePresent = true;
            }

            if ($subEvent->getStatus()->getType()->sameAs(StatusType::Unavailable())) {
                $unavailablePresent = true;
            }
        }

        if ($temporarilyUnavailablePresent) {
            return StatusType::TemporarilyUnavailable();
        }

        if ($unavailablePresent) {
            return StatusType::Unavailable();
        }

        // This extra return is needed for events with calendar type of permanent or periodic.
        return StatusType::Available();
    }

    /**
     * If the calendar has subEvents and a status manually set through an import or full calendar update
     * through the API, the top status might be incorrect.
     * For example the top status can not be Available if all the subEvents are Unavailable or TemporarilyUnavailable.
     * However we want to be flexible in what we accept from API clients since otherwise they will have to implement a
     * lot of (new) logic to make sure the top status they're sending is correct.
     * So we accept the top status as-is, and correct it during projection.
     * That way if the correction is bugged, we can always fix it and replay it with the original data.
     */
    private function determineCorrectTopStatusForProjection(Calendar $calendar): Status
    {
        // If the calendar has no subEvents, the top level status is always valid.
        if (!$calendar instanceof CalendarWithSubEvents) {
            return $calendar->getStatus();
        }

        // Get the reason when there is only one sub event and the top level status has no reason.
        $reason = null;
        if (count($calendar->getSubEvents()->toArray()) === 1 && $calendar->getStatus()->getReason() === null) {
            $reason =  $calendar->getSubEvents()->toArray()[0]->getStatus()->getReason();
        }

        // If the calendar has subEvents, the top level status is valid if it is the same type as the type derived from
        // the subEvents. In that case return $this->status so we include the top-level reason (if it has one).
        $expectedStatusType = $this->deriveStatusTypeFromSubEvents($calendar->getSubEvents());
        if ($calendar->getStatus()->getType()->toString() === $expectedStatusType->toString()) {
            // Also make sure to include the reason of a sub event when there is no reason on the top level.
            if ($calendar->getStatus()->getReason() === null) {
                return new Status($expectedStatusType, $reason);
            }

            return $calendar->getStatus();
        }

        // If the top-level status is invalid compared to the status type derived from the subEvents, return the
        // expected status type without any reason. (If the top level status had a reason it's probably not applicable
        // for the new status type.)
        return new Status($expectedStatusType, $reason);
    }

    /**
     * This method can determine the top level booking availability from the sub events
     * - For a periodic or permanent calendar this is always available
     * - If one of the sub events is available then the top level is available
     * - If all of the sub events are unavailable the top level is also unavailable
     */
    private function deriveBookingAvailabilityFromSubEvents(SubEvents $subEvents): BookingAvailability
    {
        if ($subEvents->isEmpty()) {
            return BookingAvailability::Available();
        }

        foreach ($subEvents as $subEvent) {
            if ($subEvent->getBookingAvailability()->getType()->sameAs(BookingAvailabilityType::Available())) {
                return BookingAvailability::Available();
            }
        }

        return BookingAvailability::Unavailable();
    }

    /**
     * A projection can require a potential fix:
     * - For a periodic or permanent calendar this is always available
     * - If there are sub events the top level status is calculated
     */
    private function determineCorrectTopBookingAvailabilityForProjection(Calendar $calendar): BookingAvailability
    {
        if (!$calendar instanceof CalendarWithSubEvents) {
            return BookingAvailability::Available();
        }

        return $this->deriveBookingAvailabilityFromSubEvents($calendar->getSubEvents());
    }
}
