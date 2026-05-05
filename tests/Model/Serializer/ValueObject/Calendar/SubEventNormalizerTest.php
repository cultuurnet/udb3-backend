<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailability;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailabilityType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\DateRange;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Time;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Status;
use CultuurNet\UDB3\Model\ValueObject\Calendar\StatusType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvent;
use CultuurNet\UDB3\Model\ValueObject\Contact\BookingInfo;
use CultuurNet\UDB3\Model\ValueObject\TimeImmutableRange;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

final class SubEventNormalizerTest extends TestCase
{
    private SubEventNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new SubEventNormalizer();
    }

    /**
     * @test
     */
    public function it_normalizes_basic_subevent(): void
    {
        $timezone = new DateTimeZone('Europe/Brussels');
        $startDate = new DateTimeImmutable('2025-03-15 10:00:00', $timezone);
        $endDate = new DateTimeImmutable('2025-03-15 20:00:00', $timezone);

        $subEvent = SubEvent::createAvailable(new DateRange($startDate, $endDate));

        $normalized = $this->normalizer->normalize($subEvent);

        $this->assertIsArray($normalized);
        $this->assertArrayHasKey('startDate', $normalized);
        $this->assertArrayHasKey('endDate', $normalized);
        $this->assertArrayHasKey('status', $normalized);
        $this->assertArrayHasKey('bookingAvailability', $normalized);
        $this->assertSame($startDate->format(\DateTimeInterface::ATOM), $normalized['startDate']);
        $this->assertSame($endDate->format(\DateTimeInterface::ATOM), $normalized['endDate']);
    }

    /**
     * @test
     */
    public function it_omits_booking_info_when_empty(): void
    {
        $timezone = new DateTimeZone('Europe/Brussels');
        $startDate = new DateTimeImmutable('2025-03-15 10:00:00', $timezone);
        $endDate = new DateTimeImmutable('2025-03-15 20:00:00', $timezone);

        $subEvent = SubEvent::createAvailable(new DateRange($startDate, $endDate))
            ->withBookingInfo(new BookingInfo());

        $normalized = $this->normalizer->normalize($subEvent);

        $this->assertArrayNotHasKey('bookingInfo', $normalized);
    }

    /**
     * @test
     */
    public function it_includes_booking_info_when_present(): void
    {
        $timezone = new DateTimeZone('Europe/Brussels');
        $startDate = new DateTimeImmutable('2025-03-15 10:00:00', $timezone);
        $endDate = new DateTimeImmutable('2025-03-15 20:00:00', $timezone);

        $bookingInfo = (new BookingInfo())
            ->withEmailAddress(new EmailAddress('contact@example.com'));

        $subEvent = SubEvent::createAvailable(new DateRange($startDate, $endDate))
            ->withBookingInfo($bookingInfo);

        $normalized = $this->normalizer->normalize($subEvent);

        $this->assertArrayHasKey('bookingInfo', $normalized);
        $this->assertIsArray($normalized['bookingInfo']);
        $this->assertArrayHasKey('email', $normalized['bookingInfo']);
        $this->assertSame('contact@example.com', $normalized['bookingInfo']['email']);
    }

    /**
     * @test
     */
    public function it_omits_childcare_when_null(): void
    {
        $timezone = new DateTimeZone('Europe/Brussels');
        $startDate = new DateTimeImmutable('2025-03-15 10:00:00', $timezone);
        $endDate = new DateTimeImmutable('2025-03-15 20:00:00', $timezone);

        $subEvent = SubEvent::createAvailable(new DateRange($startDate, $endDate))
            ->withChildcareTimeRange(null);

        $normalized = $this->normalizer->normalize($subEvent);

        $this->assertArrayNotHasKey('childcare', $normalized);
    }

    /**
     * @test
     */
    public function it_includes_childcare_when_present(): void
    {
        $timezone = new DateTimeZone('Europe/Brussels');
        $startDate = new DateTimeImmutable('2025-03-15 10:00:00', $timezone);
        $endDate = new DateTimeImmutable('2025-03-15 20:00:00', $timezone);

        $childcareTimeRange = new TimeImmutableRange(
            Time::fromString('09:00'),
            Time::fromString('17:00')
        );

        $subEvent = SubEvent::createAvailable(new DateRange($startDate, $endDate))
            ->withChildcareTimeRange($childcareTimeRange);

        $normalized = $this->normalizer->normalize($subEvent);

        $this->assertArrayHasKey('childcare', $normalized);
        $this->assertIsArray($normalized['childcare']);
        $this->assertSame('09:00', $normalized['childcare']['start']);
        $this->assertSame('17:00', $normalized['childcare']['end']);
    }

    /**
     * @test
     * @dataProvider statusAndAvailabilityProvider
     */
    public function it_normalizes_with_different_status_and_availability(
        Status $status,
        BookingAvailability $bookingAvailability,
        string $expectedStatusType,
        string $expectedAvailabilityType
    ): void {
        $timezone = new DateTimeZone('Europe/Brussels');
        $startDate = new DateTimeImmutable('2025-03-15 10:00:00', $timezone);
        $endDate = new DateTimeImmutable('2025-03-15 20:00:00', $timezone);

        $subEvent = SubEvent::createAvailable(new DateRange($startDate, $endDate))
            ->withStatus($status)
            ->withBookingAvailability($bookingAvailability);

        $normalized = $this->normalizer->normalize($subEvent);

        $this->assertSame($expectedStatusType, $normalized['status']['type']);
        $this->assertSame($expectedAvailabilityType, $normalized['bookingAvailability']['type']);
    }

    public function statusAndAvailabilityProvider(): array
    {
        return [
            'available_with_available_booking' => [
                new Status(StatusType::Available()),
                new BookingAvailability(BookingAvailabilityType::Available()),
                'Available',
                'Available',
            ],
            'unavailable_with_unavailable_booking' => [
                new Status(StatusType::Unavailable()),
                new BookingAvailability(BookingAvailabilityType::Unavailable()),
                'Unavailable',
                'Unavailable',
            ],
            'temporarily_unavailable_with_available_booking' => [
                new Status(StatusType::TemporarilyUnavailable()),
                new BookingAvailability(BookingAvailabilityType::Available()),
                'TemporarilyUnavailable',
                'Available',
            ],
            'temporarily_unavailable_with_unavailable_booking' => [
                new Status(StatusType::TemporarilyUnavailable()),
                new BookingAvailability(BookingAvailabilityType::Unavailable()),
                'TemporarilyUnavailable',
                'Unavailable',
            ],
        ];
    }

    /**
     * @test
     */
    public function it_supports_normalization_of_subevent(): void
    {
        $timezone = new DateTimeZone('Europe/Brussels');
        $startDate = new DateTimeImmutable('2025-03-15 10:00:00', $timezone);
        $endDate = new DateTimeImmutable('2025-03-15 20:00:00', $timezone);

        $subEvent = SubEvent::createAvailable(new DateRange($startDate, $endDate));

        $this->assertTrue($this->normalizer->supportsNormalization($subEvent));
    }

    /**
     * @test
     */
    public function it_omits_overnight_when_false(): void
    {
        $subEvent = SubEvent::createAvailable(
            new DateRange(
                new DateTimeImmutable('2026-07-01T09:00:00+02:00'),
                new DateTimeImmutable('2026-07-05T17:00:00+02:00')
            )
        );

        $normalized = $this->normalizer->normalize($subEvent);

        $this->assertArrayNotHasKey('overnight', $normalized);
    }

    /**
     * @test
     */
    public function it_includes_overnight_when_true(): void
    {
        $subEvent = SubEvent::createAvailable(
            new DateRange(
                new DateTimeImmutable('2026-07-01T09:00:00+02:00'),
                new DateTimeImmutable('2026-07-05T17:00:00+02:00')
            )
        )->withOvernight(true);

        $normalized = $this->normalizer->normalize($subEvent);

        $this->assertArrayHasKey('overnight', $normalized);
        $this->assertTrue($normalized['overnight']);
    }

    /**
     * @test
     */
    public function it_does_not_support_normalization_of_other_types(): void
    {
        $this->assertFalse($this->normalizer->supportsNormalization('string'));
        $this->assertFalse($this->normalizer->supportsNormalization(123));
        $this->assertFalse($this->normalizer->supportsNormalization([]));
        $this->assertFalse($this->normalizer->supportsNormalization(new \stdClass()));
    }

    /**
     * @test
     */
    public function it_handles_childcare_with_only_start_time(): void
    {
        $timezone = new DateTimeZone('Europe/Brussels');
        $startDate = new DateTimeImmutable('2025-03-15 10:00:00', $timezone);
        $endDate = new DateTimeImmutable('2025-03-15 20:00:00', $timezone);

        $childcareTimeRange = new TimeImmutableRange(Time::fromString('08:00'), null);

        $subEvent = SubEvent::createAvailable(new DateRange($startDate, $endDate))
            ->withChildcareTimeRange($childcareTimeRange);

        $normalized = $this->normalizer->normalize($subEvent);

        $this->assertArrayHasKey('childcare', $normalized);
        $this->assertSame('08:00', $normalized['childcare']['start']);
        $this->assertArrayNotHasKey('end', $normalized['childcare']);
    }

    /**
     * @test
     */
    public function it_handles_childcare_with_only_end_time(): void
    {
        $timezone = new DateTimeZone('Europe/Brussels');
        $startDate = new DateTimeImmutable('2025-03-15 10:00:00', $timezone);
        $endDate = new DateTimeImmutable('2025-03-15 20:00:00', $timezone);

        $childcareTimeRange = new TimeImmutableRange(null, Time::fromString('18:00'));

        $subEvent = SubEvent::createAvailable(new DateRange($startDate, $endDate))
            ->withChildcareTimeRange($childcareTimeRange);

        $normalized = $this->normalizer->normalize($subEvent);

        $this->assertArrayHasKey('childcare', $normalized);
        $this->assertSame('18:00', $normalized['childcare']['end']);
        $this->assertArrayNotHasKey('start', $normalized['childcare']);
    }
}
