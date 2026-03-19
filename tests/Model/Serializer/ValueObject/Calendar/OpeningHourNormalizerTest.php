<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Day;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Days;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Hour;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Minute;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHour;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Time;
use CultuurNet\UDB3\Model\ValueObject\TimeImmutableRange;
use PHPUnit\Framework\TestCase;

final class OpeningHourNormalizerTest extends TestCase
{
    private OpeningHourNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new OpeningHourNormalizer();
    }

    /**
     * @test
     */
    public function it_should_normalize_opening_hour(): void
    {
        $openingHour = new OpeningHour(
            new Days(Day::monday(), Day::tuesday()),
            new Time(new Hour(9), new Minute(0)),
            new Time(
                new Hour(17),
                new Minute(0)
            )
        );

        $result = $this->normalizer->normalize($openingHour);

        $this->assertSame([
            'opens' => '09:00',
            'closes' => '17:00',
            'dayOfWeek' => ['monday', 'tuesday'],
        ], $result);
    }

    /**
     * @test
     */
    public function it_should_normalize_opening_hour_with_childcare_time_range(): void
    {
        $openingHour = (new OpeningHour(
            new Days(Day::monday()),
            new Time(new Hour(9), new Minute(0)),
            new Time(new Hour(17), new Minute(0))
        ))->withChildcareTimeRange(new TimeImmutableRange(
            Time::fromString('08:00'),
            Time::fromString('18:00')
        ));

        $result = $this->normalizer->normalize($openingHour);

        $this->assertSame([
            'opens' => '09:00',
            'closes' => '17:00',
            'dayOfWeek' => ['monday'],
            'childcare' => ['start' => '08:00', 'end' => '18:00'],
        ], $result);
    }

    /**
     * @test
     */
    public function it_should_normalize_opening_hour_with_only_childcare_start(): void
    {
        $openingHour = (new OpeningHour(
            new Days(Day::monday()),
            new Time(new Hour(9), new Minute(0)),
            new Time(new Hour(17), new Minute(0))
        ))->withChildcareTimeRange(new TimeImmutableRange(Time::fromString('08:00'), null));

        $result = $this->normalizer->normalize($openingHour);

        $this->assertSame('08:00', $result['childcare']['start']);
        $this->assertArrayNotHasKey('end', $result['childcare']);
    }

    /**
     * @test
     */
    public function it_should_normalize_opening_hour_with_only_childcare_end(): void
    {
        $openingHour = (new OpeningHour(
            new Days(Day::monday()),
            new Time(new Hour(9), new Minute(0)),
            new Time(new Hour(17), new Minute(0))
        ))->withChildcareTimeRange(new TimeImmutableRange(null, Time::fromString('18:00')));

        $result = $this->normalizer->normalize($openingHour);

        $this->assertArrayNotHasKey('start', $result['childcare']);
        $this->assertSame('18:00', $result['childcare']['end']);
    }

    /**
     * @test
     */
    public function it_should_not_include_childcare_when_not_set(): void
    {
        $openingHour = new OpeningHour(
            new Days(Day::monday()),
            new Time(new Hour(9), new Minute(0)),
            new Time(new Hour(17), new Minute(0))
        );

        $result = $this->normalizer->normalize($openingHour);

        $this->assertArrayNotHasKey('childcare', $result);
    }

    /**
     * @test
     */
    public function it_should_support_normalization_for_opening_hour_class(): void
    {
        $openingHour = new OpeningHour(
            new Days(Day::monday()),
            new Time(new Hour(9), new Minute(0)),
            new Time(new Hour(17), new Minute(0))
        );
        $this->assertTrue($this->normalizer->supportsNormalization($openingHour));
    }

    /**
     * @test
     */
    public function it_should_not_support_normalization_for_other_classes(): void
    {
        $this->assertFalse($this->normalizer->supportsNormalization(new \stdClass()));
    }
}
