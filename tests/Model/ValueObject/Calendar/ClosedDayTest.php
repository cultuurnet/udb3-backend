<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\ClosedDay;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ClosedDayTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_a_closed_day_with_same_start_and_end_date(): void
    {
        $startDate = new DateTimeImmutable('2024-12-25');
        $endDate = new DateTimeImmutable('2024-12-25');

        $closedDay = new ClosedDay($startDate, $endDate);

        $this->assertEquals($startDate, $closedDay->getStartDate());
        $this->assertEquals($endDate, $closedDay->getEndDate());
        $this->assertNull($closedDay->getDescription());
    }

    /**
     * @test
     */
    public function it_creates_a_closed_day_with_different_start_and_end_date(): void
    {
        $startDate = new DateTimeImmutable('2024-12-24');
        $endDate = new DateTimeImmutable('2024-12-26');

        $closedDay = new ClosedDay($startDate, $endDate);

        $this->assertEquals($startDate, $closedDay->getStartDate());
        $this->assertEquals($endDate, $closedDay->getEndDate());
    }

    /**
     * @test
     */
    public function it_creates_a_closed_day_with_optional_description(): void
    {
        $startDate = new DateTimeImmutable('2024-12-25');
        $endDate = new DateTimeImmutable('2024-12-25');
        $description = new TranslatedAdjustedDescription(
            new Language('nl'),
            new AdjustedDescription('Gesloten op eerste kerstdag')
        );

        $closedDay = new ClosedDay($startDate, $endDate, $description);

        $this->assertEquals($startDate, $closedDay->getStartDate());
        $this->assertEquals($endDate, $closedDay->getEndDate());
        $this->assertNotNull($closedDay->getDescription());
        $this->assertEquals($description, $closedDay->getDescription());
    }

    /**
     * @test
     */
    public function it_throws_when_start_date_is_after_end_date(): void
    {
        $startDate = new DateTimeImmutable('2024-12-26');
        $endDate = new DateTimeImmutable('2024-12-25');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"startDate" should not be later than "endDate".');

        new ClosedDay($startDate, $endDate);
    }
}
