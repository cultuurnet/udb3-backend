<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar;

use CultuurNet\UDB3\Event\Event;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Day;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Days;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Hour;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Minute;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHour;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Time;
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
        $days = new Days(Day::monday(), Day::tuesday());
        $openingTime = new Time(new Hour(9), new Minute(0));
        $closingTime = new Time(new Hour(17), new Minute(0));

        $openingHour = new OpeningHour($days, $openingTime, $closingTime);

        $result = $this->normalizer->normalize($openingHour);

        $this->assertSame([
            'opens' => '09:00',
            'closes' => '17:00',
            'dayOfWeek' => ['Monday', 'Tuesday'],
        ], $result);
    }

    /**
     * @test
     */
    public function it_should_support_normalization_for_opening_hour_class(): void
    {
        $this->assertTrue($this->normalizer->supportsNormalization(OpeningHour::class));
    }

    /**
     * @test
     */
    public function it_should_not_support_normalization_for_other_classes(): void
    {
        $this->assertFalse($this->normalizer->supportsNormalization(Event::class));
    }
}
