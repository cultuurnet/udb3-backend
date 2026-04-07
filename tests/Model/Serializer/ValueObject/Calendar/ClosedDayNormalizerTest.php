<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\ClosedDay;
use CultuurNet\UDB3\Model\ValueObject\Calendar\AdjustedDescription;
use CultuurNet\UDB3\Model\ValueObject\Calendar\TranslatedAdjustedDescription;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class ClosedDayNormalizerTest extends TestCase
{
    private ClosedDayNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new ClosedDayNormalizer();
    }

    /**
     * @test
     */
    public function it_normalizes_a_closed_day_without_description(): void
    {
        $closedDay = new ClosedDay(
            new DateTimeImmutable('2024-12-25T00:00:00+00:00'),
            new DateTimeImmutable('2024-12-25T23:59:59+00:00')
        );

        $result = $this->normalizer->normalize($closedDay);

        $this->assertSame('2024-12-25', $result['startDate']);
        $this->assertSame('2024-12-25', $result['endDate']);
        $this->assertArrayNotHasKey('description', $result);
    }

    /**
     * @test
     */
    public function it_normalizes_a_closed_day_with_single_language_description(): void
    {
        $description = new TranslatedAdjustedDescription(
            new Language('nl'),
            new AdjustedDescription('Kerstfeest gesloten')
        );

        $closedDay = new ClosedDay(
            new DateTimeImmutable('2024-12-25T00:00:00+00:00'),
            new DateTimeImmutable('2024-12-26T00:00:00+00:00'),
            $description
        );

        $result = $this->normalizer->normalize($closedDay);

        $this->assertSame('2024-12-25', $result['startDate']);
        $this->assertSame('2024-12-26', $result['endDate']);
        $this->assertIsArray($result['description']);
        $this->assertSame('Kerstfeest gesloten', $result['description']['nl']);
    }

    /**
     * @test
     */
    public function it_normalizes_a_closed_day_with_multiple_language_descriptions(): void
    {
        $description = new TranslatedAdjustedDescription(
            new Language('nl'),
            new AdjustedDescription('Kerstfeest gesloten')
        );
        $description = $description->withTranslation(
            new Language('fr'),
            new AdjustedDescription('Fermé pour Noël')
        );
        $description = $description->withTranslation(
            new Language('en'),
            new AdjustedDescription('Closed for Christmas')
        );

        $closedDay = new ClosedDay(
            new DateTimeImmutable('2024-12-25T00:00:00+00:00'),
            new DateTimeImmutable('2024-12-26T00:00:00+00:00'),
            $description
        );

        $result = $this->normalizer->normalize($closedDay);

        $this->assertIsArray($result['description']);
        $this->assertCount(3, $result['description']);
        $this->assertSame('Kerstfeest gesloten', $result['description']['nl']);
        $this->assertSame('Fermé pour Noël', $result['description']['fr']);
        $this->assertSame('Closed for Christmas', $result['description']['en']);
    }

    /**
     * @test
     */
    public function it_formats_dates_as_y_m_d(): void
    {
        $closedDay = new ClosedDay(
            new DateTimeImmutable('2024-01-01T08:30:00+00:00'),
            new DateTimeImmutable('2024-01-31T18:45:00+00:00')
        );

        $result = $this->normalizer->normalize($closedDay);

        $this->assertSame('2024-01-01', $result['startDate']);
        $this->assertSame('2024-01-31', $result['endDate']);
    }
}
