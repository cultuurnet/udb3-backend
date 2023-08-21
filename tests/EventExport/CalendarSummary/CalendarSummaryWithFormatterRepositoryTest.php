<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\CalendarSummary;

use CultuurNet\CalendarSummaryV3\CalendarSummaryTester;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\TestCase;

final class CalendarSummaryWithFormatterRepositoryTest extends TestCase
{
    private const EVENT_ID = '24b87f88-d380-4746-ad40-044a007ec4c9';

    private CalendarSummaryRepositoryInterface $repository;

    public function setUp(): void
    {
        CalendarSummaryTester::setTestNow(2022);

        $eventRepository = new InMemoryDocumentRepository();
        $eventRepository->save(
            new JsonDocument(
                self::EVENT_ID,
                Json::encode(
                    [
                        '@context' => '/contexts/event',
                        'calendarType' => 'multiple',
                        'startDate' => '2022-09-23T18:00:00+00:00',
                        'endDate' => '2022-10-07T21:59:59+00:00',
                        'status' => [
                            'type' => 'Available',
                        ],
                        'bookingAvailability' => [
                            'type' => 'Available',
                        ],
                        'subEvent' => [
                            [
                                'id' => 0,
                                'status' => [
                                    'type' => 'Available',
                                    ],
                                'bookingAvailability' => [
                                    'type' => 'Available',
                                    ],
                                'startDate' => '2022-09-23T18:00:00+00:00',
                                'endDate' => '2022-09-23T21:00:00+00:00',
                                '@type' => 'Event',
                            ],
                            [
                                'id' => 1,
                                'status' => [
                                    'type' => 'Available',
                                    ],
                                'bookingAvailability' => [
                                    'type' => 'Available',
                                    ],
                                'startDate' => '2022-09-30T18:00:00+00:00',
                                'endDate' => '2022-09-30T21:00:00+00:00',
                                '@type' => 'Event',
                            ],
                            [
                                'id' => 2,
                                'status' => [
                                    'type' => 'Available',
                                ],
                                'bookingAvailability' => [
                                    'type' => 'Available',
                                    ],
                                'startDate' => '2022-10-06T22:00:00+00:00',
                                'endDate' => '2022-10-07T21:59:59+00:00',
                                '@type' => 'Event',
                            ],
                        ],
                    ]
                )
            )
        );
        $this->repository = new CalendarSummaryWithFormatterRepository($eventRepository);
    }

    /**
     * @test
     * @dataProvider calendarSummaryProvider
     */
    public function it_returns_a_calendar_summary_for_various_content_types_and_formats(
        string $result,
        ContentType $contentType,
        Format $format
    ): void {
        $this->assertEquals(
            $result,
            $this->repository->get(self::EVENT_ID, $contentType, $format)
        );
    }

    public function calendarSummaryProvider(): array
    {
        return [
            [
                'result' => '23 sep - 7 okt',
                'contentType' => ContentType::plain(),
                'format' => Format::xs(),
            ],
            [
                'result' => 'Vr 23 sep - vr 7 okt',
                'contentType' => ContentType::plain(),
                'format' => Format::sm(),
            ],
            [
                'result' => 'Vr 23 september 2022' . PHP_EOL .
                    'Vr 30 september 2022' . PHP_EOL .
                    'Vr 7 oktober 2022',
                'contentType' => ContentType::plain(),
                'format' => Format::md(),
            ],
            [
                'result' => 'Vrijdag 23 september 2022 van 20:00 tot 23:00' . PHP_EOL .
                    'Vrijdag 30 september 2022 van 20:00 tot 23:00' . PHP_EOL .
                    'Vrijdag 7 oktober 2022',
                'contentType' => ContentType::plain(),
                'format' => Format::lg(),
            ],
            [
                'result' => '<span class="cf-date">23</span> <span class="cf-month">sep</span> <span class="cf-year">2022</span> - <span class="cf-date">7</span> <span class="cf-month">okt</span>',
                'contentType' => ContentType::html(),
                'format' => Format::xs(),
            ],
            [
                'result' => '<span class="cf-weekday cf-meta">Vr</span> <span class="cf-date">23 sep</span> <span class="cf-to cf-meta">-</span> <span class="cf-weekday cf-meta">vr</span> <span class="cf-date">7 okt</span>',
                'contentType' => ContentType::html(),
                'format' => Format::sm(),
            ],
            [
                'result' => '<ul class="cnw-event-date-info">' .
                    '<li><span class="cf-weekday cf-meta">Vr</span> <span class="cf-date">23 september 2022</span></li>' .
                    '<li><span class="cf-weekday cf-meta">Vr</span> <span class="cf-date">30 september 2022</span></li>' .
                    '<li><span class="cf-weekday cf-meta">Vr</span> <span class="cf-date">7 oktober 2022</span></li></ul>',
                'contentType' => ContentType::html(),
                'format' => Format::md(),
            ],
            [
                'result' => '<ul class="cnw-event-date-info">' .
                    '<li><time itemprop="startDate" datetime="2022-09-23T20:00:00+02:00"><span class="cf-weekday cf-meta">Vrijdag</span> <span class="cf-date">23 september 2022</span> <span class="cf-from cf-meta">van</span> <span class="cf-time">20:00</span></time> <span class="cf-to cf-meta">tot</span> <time itemprop="endDate" datetime="2022-09-23T23:00:00+02:00"><span class="cf-time">23:00</span></time></li>' .
                    '<li><time itemprop="startDate" datetime="2022-09-30T20:00:00+02:00"><span class="cf-weekday cf-meta">Vrijdag</span> <span class="cf-date">30 september 2022</span> <span class="cf-from cf-meta">van</span> <span class="cf-time">20:00</span></time> <span class="cf-to cf-meta">tot</span> <time itemprop="endDate" datetime="2022-09-30T23:00:00+02:00"><span class="cf-time">23:00</span></time></li>' .
                    '<li><time itemprop="startDate" datetime="2022-10-07T00:00:00+02:00"><span class="cf-weekday cf-meta">Vrijdag</span> <span class="cf-date">7 oktober 2022</span></time></li></ul>',
                'contentType' => ContentType::html(),
                'format' => Format::lg(),
            ],
        ];
    }
}
