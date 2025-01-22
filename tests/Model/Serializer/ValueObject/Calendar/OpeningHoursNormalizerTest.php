<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar;

use PHPUnit\Framework\TestCase;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHour;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Days;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Time;

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
    public function it_should_normalize_opening_hour(): void
    {
        $days = new Days(['Monday', 'Tuesday']);
        $openingTime = new Time('09:00');
        $closingTime = new Time('17:00');

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
        $this->assertFalse($this->normalizer->supportsNormalization(SomeOtherClass::class));
    }
}
