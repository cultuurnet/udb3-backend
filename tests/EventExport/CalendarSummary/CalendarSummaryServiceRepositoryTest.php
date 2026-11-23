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

final class CalendarSummaryServiceRepositoryTest extends TestCase
{
    private const EVENT_ID = '24b87f88-d380-4746-ad40-044a007ec4c9';

    private CalendarSummaryService&MockObject $calendarSummaryService;

    private CalendarSummaryServiceRepository $repository;

    protected function setUp(): void
    {
        $this->calendarSummaryService = $this->createMock(CalendarSummaryService::class);

        $this->repository = new CalendarSummaryServiceRepository($this->calendarSummaryService);
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

    /**
     * @test
     */
    public function it_fetches_each_size_and_style_once_for_all_prefetched_events(): void
    {
        $otherEventId = '8c0af9dd-6542-4c3a-b4ea-f2ba44312e5f';
        $this->repository->prefetch([self::EVENT_ID, $otherEventId]);

        $this->calendarSummaryService->expects($this->never())->method('get');
        $this->calendarSummaryService->expects($this->exactly(2))
            ->method('getMany')
            ->willReturnCallback(
                function (OfferType $offerType, array $eventIds, array $parameters) use ($otherEventId): array {
                    $this->assertEquals(OfferType::event(), $offerType);
                    $this->assertSame([self::EVENT_ID, $otherEventId], $eventIds);
                    $this->assertSame('nl', $parameters['language']);
                    $this->assertSame('false', $parameters['hidePast']);
                    $size = $parameters['size'];
                    return [self::EVENT_ID => 'first ' . $size, $otherEventId => 'other ' . $size];
                }
            );

        $this->assertSame('first md', $this->repository->get(self::EVENT_ID, ContentType::plain(), Format::md()));
        $this->assertSame('first lg', $this->repository->get(self::EVENT_ID, ContentType::plain(), Format::lg()));
        $this->assertSame('other md', $this->repository->get($otherEventId, ContentType::plain(), Format::md()));
        $this->assertSame('other lg', $this->repository->get($otherEventId, ContentType::plain(), Format::lg()));
    }

    /**
     * @test
     */
    public function it_fetches_again_for_the_next_prefetched_events(): void
    {
        $nextEventId = '8c0af9dd-6542-4c3a-b4ea-f2ba44312e5f';

        $this->calendarSummaryService->expects($this->exactly(2))
            ->method('getMany')
            ->willReturnOnConsecutiveCalls([self::EVENT_ID => 'first'], [$nextEventId => 'next']);

        $this->repository->prefetch([self::EVENT_ID]);
        $this->assertSame('first', $this->repository->get(self::EVENT_ID, ContentType::plain(), Format::md()));

        $this->repository->prefetch([$nextEventId]);
        $this->assertSame('next', $this->repository->get($nextEventId, ContentType::plain(), Format::md()));
    }

    /**
     * @test
     */
    public function it_throws_summary_unavailable_for_a_prefetched_event_without_summary(): void
    {
        $this->repository->prefetch([self::EVENT_ID]);
        $this->calendarSummaryService->method('getMany')->willReturn([]);

        $this->expectException(SummaryUnavailableException::class);

        $this->repository->get(self::EVENT_ID, ContentType::plain(), Format::md());
    }

    /**
     * @test
     */
    public function it_does_not_repeat_a_failed_fetch_for_the_other_prefetched_events(): void
    {
        $otherEventId = '8c0af9dd-6542-4c3a-b4ea-f2ba44312e5f';
        $this->repository->prefetch([self::EVENT_ID, $otherEventId]);

        $this->calendarSummaryService->expects($this->once())
            ->method('getMany')
            ->willThrowException(new RuntimeException('Search API returned HTTP status 500'));

        foreach ([self::EVENT_ID, $otherEventId] as $eventId) {
            try {
                $this->repository->get($eventId, ContentType::plain(), Format::md());
                $this->fail('Expected the summary to be unavailable');
            } catch (SummaryUnavailableException) {
            }
        }
    }

    private function expectSummaryWithParameters(array $parameters, string $summary): void
    {
        $this->calendarSummaryService->expects($this->once())
            ->method('get')
            ->with(OfferType::event(), self::EVENT_ID, $parameters)
            ->willReturn($summary);
    }
}
