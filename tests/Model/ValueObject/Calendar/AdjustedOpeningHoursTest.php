<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Day;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Days;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHour;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Time;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class AdjustedOpeningHoursTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_with_valid_dates_and_opening_hours(): void
    {
        $adjustedOpeningHours = new OpeningHoursAdjusted(
            new DateTimeImmutable('2026-12-21'),
            new DateTimeImmutable('2026-12-26'),
            new OpeningHours(
                new OpeningHour(new Days(Day::friday()), Time::fromString('13:00'), Time::fromString('15:00'))
            )
        );

        $this->assertSame('2026-12-21', $adjustedOpeningHours->getStartDate()->format('Y-m-d'));
        $this->assertSame('2026-12-26', $adjustedOpeningHours->getEndDate()->format('Y-m-d'));
        $this->assertCount(1, $adjustedOpeningHours->getOpeningHours()->toArray());
        $this->assertNull($adjustedOpeningHours->getDescription());
    }

    /**
     * @test
     */
    public function it_creates_with_optional_description(): void
    {
        $description = new TranslatedOpeningHoursAdjustedDescription(
            new Language('nl'),
            new AdjustedDescription('Kerstvakantie')
        );

        $adjustedOpeningHours = new OpeningHoursAdjusted(
            new DateTimeImmutable('2026-12-21'),
            new DateTimeImmutable('2026-12-26'),
            new OpeningHours(
                new OpeningHour(new Days(Day::friday()), Time::fromString('13:00'), Time::fromString('15:00'))
            ),
            $description
        );

        $this->assertSame($description, $adjustedOpeningHours->getDescription());
    }

    /**
     * @test
     */
    public function it_creates_with_same_day_start_and_end_date(): void
    {
        $adjustedOpeningHours = new OpeningHoursAdjusted(
            new DateTimeImmutable('2026-12-25'),
            new DateTimeImmutable('2026-12-25'),
            new OpeningHours(
                new OpeningHour(new Days(Day::friday()), Time::fromString('10:00'), Time::fromString('16:00'))
            )
        );

        $this->assertSame('2026-12-25', $adjustedOpeningHours->getStartDate()->format('Y-m-d'));
        $this->assertSame('2026-12-25', $adjustedOpeningHours->getEndDate()->format('Y-m-d'));
    }

    /**
     * @test
     */
    public function it_throws_when_start_date_is_after_end_date(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"startDate" should not be later than "endDate".');

        new OpeningHoursAdjusted(
            new DateTimeImmutable('2026-12-26'),
            new DateTimeImmutable('2026-12-21'),
            new OpeningHours(
                new OpeningHour(new Days(Day::friday()), Time::fromString('13:00'), Time::fromString('15:00'))
            )
        );
    }

    /**
     * @test
     */
    public function it_throws_when_opening_hours_is_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('AdjustedOpeningHours must contain at least one OpeningHour.');

        new OpeningHoursAdjusted(
            new DateTimeImmutable('2026-12-21'),
            new DateTimeImmutable('2026-12-26'),
            new OpeningHours()
        );
    }
}
