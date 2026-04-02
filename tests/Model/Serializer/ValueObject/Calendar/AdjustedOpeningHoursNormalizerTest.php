<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\AdjustedOpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\AdjustedOpeningHoursDescription;
use CultuurNet\UDB3\Model\ValueObject\Calendar\TranslatedAdjustedOpeningHoursDescription;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Day;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Days;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHour;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Time;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class AdjustedOpeningHoursNormalizerTest extends TestCase
{
    private AdjustedOpeningHoursNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new AdjustedOpeningHoursNormalizer();
    }

    /**
     * @test
     */
    public function it_normalizes_adjusted_opening_hours_without_description(): void
    {
        $adjustedOpeningHours = new AdjustedOpeningHours(
            new DateTimeImmutable('2026-12-21'),
            new DateTimeImmutable('2026-12-26'),
            new OpeningHours(
                new OpeningHour(
                    new Days(Day::friday()),
                    Time::fromString('13:00'),
                    Time::fromString('15:00')
                )
            )
        );

        $result = $this->normalizer->normalize($adjustedOpeningHours);

        $this->assertSame('2026-12-21', $result['startDate']);
        $this->assertSame('2026-12-26', $result['endDate']);
        $this->assertArrayHasKey('openingHours', $result);
        $this->assertCount(1, $result['openingHours']);
        $this->assertArrayNotHasKey('description', $result);
    }

    /**
     * @test
     */
    public function it_normalizes_adjusted_opening_hours_with_description(): void
    {
        $description = new TranslatedAdjustedOpeningHoursDescription(
            new Language('nl'),
            new AdjustedOpeningHoursDescription('Kerstvakantie')
        );
        $description = $description->withTranslation(
            new Language('fr'),
            new AdjustedOpeningHoursDescription('Vacances de Noël')
        );

        $adjustedOpeningHours = new AdjustedOpeningHours(
            new DateTimeImmutable('2026-12-21'),
            new DateTimeImmutable('2026-12-26'),
            new OpeningHours(
                new OpeningHour(
                    new Days(Day::friday()),
                    Time::fromString('13:00'),
                    Time::fromString('15:00')
                )
            ),
            $description
        );

        $result = $this->normalizer->normalize($adjustedOpeningHours);

        $this->assertSame('2026-12-21', $result['startDate']);
        $this->assertSame('2026-12-26', $result['endDate']);
        $this->assertIsArray($result['description']);
        $this->assertSame('Kerstvakantie', $result['description']['nl']);
        $this->assertSame('Vacances de Noël', $result['description']['fr']);
    }

    /**
     * @test
     */
    public function it_normalizes_opening_hours_within_adjusted_opening_hours(): void
    {
        $adjustedOpeningHours = new AdjustedOpeningHours(
            new DateTimeImmutable('2026-12-27'),
            new DateTimeImmutable('2026-12-31'),
            new OpeningHours(
                new OpeningHour(
                    new Days(Day::saturday(), Day::sunday()),
                    Time::fromString('14:00'),
                    Time::fromString('16:00')
                )
            )
        );

        $result = $this->normalizer->normalize($adjustedOpeningHours);

        $this->assertCount(1, $result['openingHours']);
        $this->assertSame('14:00', $result['openingHours'][0]['opens']);
        $this->assertSame('16:00', $result['openingHours'][0]['closes']);
        $this->assertContains('saturday', $result['openingHours'][0]['dayOfWeek']);
        $this->assertContains('sunday', $result['openingHours'][0]['dayOfWeek']);
    }

    /**
     * @test
     */
    public function it_formats_dates_as_y_m_d(): void
    {
        $adjustedOpeningHours = new AdjustedOpeningHours(
            new DateTimeImmutable('2026-01-01T08:30:00+00:00'),
            new DateTimeImmutable('2026-01-31T18:45:00+00:00'),
            new OpeningHours()
        );

        $result = $this->normalizer->normalize($adjustedOpeningHours);

        $this->assertSame('2026-01-01', $result['startDate']);
        $this->assertSame('2026-01-31', $result['endDate']);
    }

    /**
     * @test
     */
    public function it_supports_normalization_of_adjusted_opening_hours(): void
    {
        $adjustedOpeningHours = new AdjustedOpeningHours(
            new DateTimeImmutable('2026-12-21'),
            new DateTimeImmutable('2026-12-26'),
            new OpeningHours()
        );

        $this->assertTrue($this->normalizer->supportsNormalization($adjustedOpeningHours));
        $this->assertFalse($this->normalizer->supportsNormalization(new \stdClass()));
        $this->assertFalse($this->normalizer->supportsNormalization('some string'));
    }
}
