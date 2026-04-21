<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar\OpeningHours;

use CultuurNet\UDB3\Model\ValueObject\Calendar\AdjustedDescription;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\ClosedDay;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\ClosedDays;
use CultuurNet\UDB3\Model\ValueObject\Calendar\TranslatedAdjustedDescription;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class ClosedDaysDenormalizerTest extends TestCase
{
    private ClosedDaysDenormalizer $denormalizer;

    protected function setUp(): void
    {
        $this->denormalizer = new ClosedDaysDenormalizer();
    }

    /**
     * @test
     */
    public function it_denormalizes_an_empty_array_to_empty_closed_days(): void
    {
        $result = $this->denormalizer->denormalize([], ClosedDays::class);

        $this->assertInstanceOf(ClosedDays::class, $result);
        $this->assertTrue($result->isEmpty());
        $this->assertEquals(0, $result->count());
    }

    /**
     * @test
     */
    public function it_denormalizes_a_non_array_to_empty_closed_days(): void
    {
        $result = $this->denormalizer->denormalize(null, ClosedDays::class);

        $this->assertInstanceOf(ClosedDays::class, $result);
        $this->assertTrue($result->isEmpty());
    }

    /**
     * @test
     */
    public function it_denormalizes_a_single_closed_day_without_description(): void
    {
        $data = [
            [
                'startDate' => '2024-12-25T00:00:00+00:00',
                'endDate' => '2024-12-25T23:59:59+00:00',
            ],
        ];

        $result = $this->denormalizer->denormalize($data, ClosedDays::class);

        $this->assertInstanceOf(ClosedDays::class, $result);
        $this->assertFalse($result->isEmpty());
        $this->assertEquals(1, $result->count());

        $closedDays = $result->toArray();
        $this->assertInstanceOf(ClosedDay::class, $closedDays[0]);
        $this->assertEquals(new DateTimeImmutable('2024-12-25T00:00:00+00:00'), $closedDays[0]->getStartDate());
        $this->assertEquals(new DateTimeImmutable('2024-12-25T23:59:59+00:00'), $closedDays[0]->getEndDate());
        $this->assertNull($closedDays[0]->getDescription());
    }

    /**
     * @test
     */
    public function it_denormalizes_a_single_closed_day_with_description(): void
    {
        $data = [
            [
                'startDate' => '2024-12-25T00:00:00+00:00',
                'endDate' => '2024-12-26T00:00:00+00:00',
                'description' => [
                    'nl' => 'Kerstfeest gesloten',
                    'fr' => 'Fermé pour Noël',
                ],
            ],
        ];

        $result = $this->denormalizer->denormalize($data, ClosedDays::class);

        $this->assertEquals(1, $result->count());

        $closedDays = $result->toArray();
        $closedDay = $closedDays[0];

        $this->assertNotNull($closedDay->getDescription());
        $this->assertInstanceOf(TranslatedAdjustedDescription::class, $closedDay->getDescription());

        $nlDescription = $closedDay->getDescription()->getTranslation(new Language('nl'));
        $this->assertInstanceOf(AdjustedDescription::class, $nlDescription);
        $this->assertEquals('Kerstfeest gesloten', $nlDescription->toString());

        $frDescription = $closedDay->getDescription()->getTranslation(new Language('fr'));
        $this->assertEquals('Fermé pour Noël', $frDescription->toString());
    }

    /**
     * @test
     */
    public function it_sorts_closed_days_by_start_date(): void
    {
        $data = [
            [
                'startDate' => '2024-12-25T00:00:00+00:00',
                'endDate' => '2024-12-25T23:59:59+00:00',
            ],
            [
                'startDate' => '2024-01-01T00:00:00+00:00',
                'endDate' => '2024-01-01T23:59:59+00:00',
            ],
            [
                'startDate' => '2024-07-21T00:00:00+00:00',
                'endDate' => '2024-07-21T23:59:59+00:00',
            ],
        ];

        $result = $this->denormalizer->denormalize($data, ClosedDays::class);

        $this->assertEquals(3, $result->count());

        $closedDays = $result->toArray();
        $this->assertEquals(new DateTimeImmutable('2024-01-01T00:00:00+00:00'), $closedDays[0]->getStartDate());
        $this->assertEquals(new DateTimeImmutable('2024-07-21T00:00:00+00:00'), $closedDays[1]->getStartDate());
        $this->assertEquals(new DateTimeImmutable('2024-12-25T00:00:00+00:00'), $closedDays[2]->getStartDate());
    }

    /**
     * @test
     */
    public function it_supports_denormalization_of_closed_days(): void
    {
        $this->assertTrue($this->denormalizer->supportsDenormalization([], ClosedDays::class));
        $this->assertFalse($this->denormalizer->supportsDenormalization([], 'SomeOtherClass'));
    }
}
