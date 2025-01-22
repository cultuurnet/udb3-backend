<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Day;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Days;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Hour;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Minute;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHour;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Time;
use PHPUnit\Framework\TestCase;

/**
 * @covers \CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar\OpeningHoursNormalizer
 */
final class OpeningHoursNormalizerTest extends TestCase
{
    private OpeningHoursNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new OpeningHoursNormalizer();
    }

    /**
     * @test
     */
    public function it_should_normalize_opening_hours(): void
    {
        $openingHour1 = new OpeningHour(
            new Days(Day::monday(), Day::tuesday()),
            new Time(new Hour(9), new Minute(0)),
            new Time(
                new Hour(17),
                new Minute(0)
            )
        );

        $openingHour2 = new OpeningHour(
            new Days(Day::wednesday(), Day::friday()),
            new Time(new Hour(10), new Minute(0)),
            new Time(
                new Hour(15),
                new Minute(0)
            )
        );

        $openingHours = new OpeningHours($openingHour1, $openingHour2);

        $result = $this->normalizer->normalize($openingHours);

        $this->assertSame([
            [
                'opens' => '09:00',
                'closes' => '17:00',
                'dayOfWeek' => ['monday', 'tuesday'],
            ],
            [
                'opens' => '10:00',
                'closes' => '15:00',
                'dayOfWeek' => ['wednesday', 'friday'],
            ],
        ], $result);
    }

    /**
     * @test
     */
    public function it_should_support_normalization_for_opening_hours_class(): void
    {
        $openingHours = $this->createMock(OpeningHours::class);
        $this->assertTrue($this->normalizer->supportsNormalization($openingHours));
    }

    /**
     * @test
     */
    public function it_should_not_support_normalization_for_other_classes(): void
    {
        $this->assertFalse($this->normalizer->supportsNormalization(new \stdClass()));
    }
}
