<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar\OpenHours;

use CultuurNet\UDB3\Model\ValueObject\Calendar\AdjustedDescription;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\AdjustedDay;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\AdjustedDays;
use CultuurNet\UDB3\Model\ValueObject\Calendar\TranslatedAdjustedDescription;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use PHPUnit\Framework\TestCase;

final class AdjustedDaysDenormalizerTest extends TestCase
{
    private AdjustedDaysDenormalizer $denormalizer;

    protected function setUp(): void
    {
        $this->denormalizer = new AdjustedDaysDenormalizer();
    }

    /**
     * @test
     */
    public function it_denormalizes_a_non_array_to_empty_collection(): void
    {
        $result = $this->denormalizer->denormalize(null, AdjustedDays::class);

        $this->assertInstanceOf(AdjustedDays::class, $result);
        $this->assertTrue($result->isEmpty());
    }

    /**
     * @test
     */
    public function it_denormalizes_an_empty_array_to_empty_collection(): void
    {
        $result = $this->denormalizer->denormalize([], AdjustedDays::class);

        $this->assertInstanceOf(AdjustedDays::class, $result);
        $this->assertTrue($result->isEmpty());
        $this->assertEquals(0, $result->count());
    }

    /**
     * @test
     */
    public function it_denormalizes_a_single_entry_without_description(): void
    {
        $data = [
            [
                'startDate' => '2026-12-21',
                'endDate' => '2026-12-26',
                'openingHours' => [
                    [
                        'opens' => '13:00',
                        'closes' => '15:00',
                        'dayOfWeek' => ['friday'],
                    ],
                ],
            ],
        ];

        $result = $this->denormalizer->denormalize($data, AdjustedDays::class);

        $this->assertFalse($result->isEmpty());
        $this->assertEquals(1, $result->count());

        $entries = $result->toArray();
        $this->assertInstanceOf(AdjustedDay::class, $entries[0]);
        $this->assertSame('2026-12-21', $entries[0]->getStartDate()->format('Y-m-d'));
        $this->assertSame('2026-12-26', $entries[0]->getEndDate()->format('Y-m-d'));
        $this->assertNull($entries[0]->getDescription());
    }

    /**
     * @test
     */
    public function it_denormalizes_a_single_entry_with_description(): void
    {
        $data = [
            [
                'startDate' => '2026-12-21',
                'endDate' => '2026-12-26',
                'openingHours' => [
                    [
                        'opens' => '13:00',
                        'closes' => '15:00',
                        'dayOfWeek' => ['friday'],
                    ],
                ],
                'description' => [
                    'nl' => 'Kerstvakantie',
                    'fr' => 'Vacances de Noël',
                ],
            ],
        ];

        $result = $this->denormalizer->denormalize($data, AdjustedDays::class);

        $this->assertEquals(1, $result->count());

        $entries = $result->toArray();
        $this->assertNotNull($entries[0]->getDescription());
        $this->assertInstanceOf(TranslatedAdjustedDescription::class, $entries[0]->getDescription());

        $nlDescription = $entries[0]->getDescription()->getTranslation(new Language('nl'));
        $this->assertInstanceOf(AdjustedDescription::class, $nlDescription);
        $this->assertEquals('Kerstvakantie', $nlDescription->toString());

        $frDescription = $entries[0]->getDescription()->getTranslation(new Language('fr'));
        $this->assertEquals('Vacances de Noël', $frDescription->toString());
    }

    /**
     * @test
     */
    public function it_skips_entries_missing_required_fields(): void
    {
        $data = [
            [
                'startDate' => '2026-12-21',
                'endDate' => '2026-12-26',
                'openingHours' => [['opens' => '13:00', 'closes' => '15:00', 'dayOfWeek' => ['friday']]],
            ],
            // Missing startDate
            [
                'endDate' => '2026-12-26',
                'openingHours' => [['opens' => '13:00', 'closes' => '15:00', 'dayOfWeek' => ['friday']]],
            ],
            // Missing openingHours
            [
                'startDate' => '2026-12-27',
                'endDate' => '2026-12-31',
            ],
            [
                'startDate' => '2026-12-27',
                'endDate' => '2026-12-31',
                'openingHours' => [['opens' => '14:00', 'closes' => '16:00', 'dayOfWeek' => ['saturday']]],
            ],
        ];

        $result = $this->denormalizer->denormalize($data, AdjustedDays::class);

        $this->assertEquals(2, $result->count());
    }

    /**
     * @test
     */
    public function it_returns_entries_sorted_by_start_date_via_collection(): void
    {
        $data = [
            [
                'startDate' => '2026-12-27',
                'endDate' => '2026-12-31',
                'openingHours' => [['opens' => '14:00', 'closes' => '16:00', 'dayOfWeek' => ['saturday']]],
            ],
            [
                'startDate' => '2026-12-21',
                'endDate' => '2026-12-26',
                'openingHours' => [['opens' => '13:00', 'closes' => '15:00', 'dayOfWeek' => ['friday']]],
            ],
        ];

        $result = $this->denormalizer->denormalize($data, AdjustedDays::class);

        $this->assertEquals(2, $result->count());

        $entries = $result->toArray();
        $this->assertSame('2026-12-21', $entries[0]->getStartDate()->format('Y-m-d'));
        $this->assertSame('2026-12-27', $entries[1]->getStartDate()->format('Y-m-d'));
    }

    /**
     * @test
     */
    public function it_skips_entries_with_empty_opening_hours(): void
    {
        $data = [
            [
                'startDate' => '2026-12-21',
                'endDate' => '2026-12-26',
                'openingHours' => [],
            ],
            [
                'startDate' => '2026-12-27',
                'endDate' => '2026-12-31',
                'openingHours' => [['opens' => '14:00', 'closes' => '16:00', 'dayOfWeek' => ['saturday']]],
            ],
        ];

        $result = $this->denormalizer->denormalize($data, AdjustedDays::class);

        $this->assertEquals(1, $result->count());
        $this->assertSame('2026-12-27', $result->toArray()[0]->getStartDate()->format('Y-m-d'));
    }

    /**
     * @test
     */
    public function it_treats_empty_description_array_as_no_description(): void
    {
        $data = [
            [
                'startDate' => '2026-12-21',
                'endDate' => '2026-12-26',
                'openingHours' => [
                    ['opens' => '13:00', 'closes' => '15:00', 'dayOfWeek' => ['friday']],
                ],
                'description' => [],
            ],
        ];

        $result = $this->denormalizer->denormalize($data, AdjustedDays::class);

        $this->assertEquals(1, $result->count());
        $this->assertNull($result->toArray()[0]->getDescription());
    }

    /**
     * @test
     */
    public function it_supports_denormalization_of_adjusted_opening_hours_collection(): void
    {
        $this->assertTrue($this->denormalizer->supportsDenormalization([], AdjustedDays::class));
        $this->assertFalse($this->denormalizer->supportsDenormalization([], 'SomeOtherClass'));
    }
}
