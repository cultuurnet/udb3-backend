<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar\OpeningHours;

use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHour;
use PHPUnit\Framework\TestCase;

final class OpeningHourDenormalizerTest extends TestCase
{
    private OpeningHourDenormalizer $denormalizer;

    protected function setUp(): void
    {
        $this->denormalizer = new OpeningHourDenormalizer();
    }

    /**
     * @test
     */
    public function it_denormalizes_an_opening_hour_without_childcare(): void
    {
        $openingHour = $this->denormalizer->denormalize(
            [
                'opens' => '09:00',
                'closes' => '17:00',
                'dayOfWeek' => ['monday', 'tuesday'],
            ],
            OpeningHour::class
        );

        $this->assertSame('09:00', $openingHour->getOpeningTime()->getValue());
        $this->assertSame('17:00', $openingHour->getClosingTime()->getValue());
        $this->assertNull($openingHour->getChildcareTimeRange());
    }

    /**
     * @test
     */
    public function it_denormalizes_an_opening_hour_with_childcare_start_and_end(): void
    {
        $openingHour = $this->denormalizer->denormalize(
            [
                'opens' => '09:00',
                'closes' => '17:00',
                'dayOfWeek' => ['monday'],
                'childcare' => ['start' => '08:00', 'end' => '18:00'],
            ],
            OpeningHour::class
        );

        $childcare = $openingHour->getChildcareTimeRange();
        $this->assertNotNull($childcare);
        $this->assertSame('08:00', $childcare->getStart()->getValue());
        $this->assertSame('18:00', $childcare->getEnd()->getValue());
    }

    /**
     * @test
     */
    public function it_denormalizes_an_opening_hour_with_childcare_start_only(): void
    {
        $openingHour = $this->denormalizer->denormalize(
            [
                'opens' => '09:00',
                'closes' => '17:00',
                'dayOfWeek' => ['monday'],
                'childcare' => ['start' => '08:00'],
            ],
            OpeningHour::class
        );

        $childcare = $openingHour->getChildcareTimeRange();
        $this->assertNotNull($childcare);
        $this->assertSame('08:00', $childcare->getStart()->getValue());
        $this->assertNull($childcare->getEnd());
    }

    /**
     * @test
     */
    public function it_denormalizes_an_opening_hour_with_childcare_end_only(): void
    {
        $openingHour = $this->denormalizer->denormalize(
            [
                'opens' => '09:00',
                'closes' => '17:00',
                'dayOfWeek' => ['monday'],
                'childcare' => ['end' => '18:00'],
            ],
            OpeningHour::class
        );

        $childcare = $openingHour->getChildcareTimeRange();
        $this->assertNotNull($childcare);
        $this->assertNull($childcare->getStart());
        $this->assertSame('18:00', $childcare->getEnd()->getValue());
    }

    /**
     * @test
     */
    public function it_does_not_set_childcare_when_childcare_key_is_absent(): void
    {
        $openingHour = $this->denormalizer->denormalize(
            [
                'opens' => '09:00',
                'closes' => '17:00',
                'dayOfWeek' => ['monday'],
            ],
            OpeningHour::class
        );

        $this->assertNull($openingHour->getChildcareTimeRange());
    }

    /**
     * @test
     */
    public function it_does_not_set_childcare_when_childcare_is_empty(): void
    {
        $openingHour = $this->denormalizer->denormalize(
            [
                'opens' => '09:00',
                'closes' => '17:00',
                'dayOfWeek' => ['monday'],
                'childcare' => [],
            ],
            OpeningHour::class
        );

        $this->assertNull($openingHour->getChildcareTimeRange());
    }

    /**
     * @test
     */
    public function it_supports_denormalization_of_opening_hour_class(): void
    {
        $this->assertTrue($this->denormalizer->supportsDenormalization([], OpeningHour::class));
    }
}
