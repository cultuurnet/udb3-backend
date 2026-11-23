<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\CalendarSummary;

use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\Search\CalendarSummaryService;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class Sapi3CalendarSummaryRepositoryTest extends TestCase
{
    private const EVENT_ID = '24b87f88-d380-4746-ad40-044a007ec4c9';

    private CalendarSummaryService&MockObject $calendarSummaryService;

    private Sapi3CalendarSummaryRepository $repository;

    protected function setUp(): void
    {
        $this->calendarSummaryService = $this->createMock(CalendarSummaryService::class);

        $this->repository = new Sapi3CalendarSummaryRepository($this->calendarSummaryService);
    }

    /**
     * @test
     */
    public function it_gets_a_text_summary_of_an_event(): void
    {
        $this->expectSummaryWithParameters(
            [
                'language' => 'nl',
                'style' => 'text',
                'size' => 'md',
                'hidePast' => 'false',
                'timezone' => 'Europe/Brussels',
            ],
            'Vr 23 sep - vr 7 okt'
        );

        $this->assertEquals(
            'Vr 23 sep - vr 7 okt',
            $this->repository->get(self::EVENT_ID, ContentType::plain(), Format::md())
        );
    }

    /**
     * @test
     */
    public function it_gets_a_html_summary_of_an_event(): void
    {
        $this->expectSummaryWithParameters(
            [
                'language' => 'nl',
                'style' => 'html',
                'size' => 'lg',
                'hidePast' => 'false',
                'timezone' => 'Europe/Brussels',
            ],
            '<span>summary</span>'
        );

        $this->assertEquals(
            '<span>summary</span>',
            $this->repository->get(self::EVENT_ID, ContentType::html(), Format::lg())
        );
    }

    /**
     * @test
     * @dataProvider unavailableSummaryProvider
     */
    public function it_throws_summary_unavailable_when_the_summary_cannot_be_fetched(Exception $exception): void
    {
        $this->calendarSummaryService->method('get')->willThrowException($exception);

        $this->expectException(SummaryUnavailableException::class);

        $this->repository->get(self::EVENT_ID, ContentType::plain(), Format::md());
    }

    public function unavailableSummaryProvider(): array
    {
        return [
            'not found' => [DocumentDoesNotExist::withId(self::EVENT_ID)],
            'failed' => [new RuntimeException('Search API returned HTTP status 500')],
        ];
    }

    private function expectSummaryWithParameters(array $parameters, string $summary): void
    {
        $this->calendarSummaryService->expects($this->once())
            ->method('get')
            ->with(OfferType::event(), self::EVENT_ID, $parameters)
            ->willReturn($summary);
    }
}
