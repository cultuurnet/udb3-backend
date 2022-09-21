<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\CalendarSummary;

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
        $documentRepository = new InMemoryDocumentRepository();
        $documentRepository->save(
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
        $this->repository = new CalendarSummaryWithFormatterRepository($documentRepository);
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
                'result' => 'Van 23/9/22 tot 7/10/22',
                'contentType' => ContentType::plain(),
                'format' => Format::xs(),
            ],
            [
                'result' => 'Van 23 september 2022 tot 7 oktober 2022',
                'contentType' => ContentType::plain(),
                'format' => Format::sm(),
            ],
            [
                'result' => 'Vrijdag 23 september 2022' . PHP_EOL .
                    'Vrijdag 30 september 2022' . PHP_EOL .
                    'Vrijdag 7 oktober 2022',
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
                'result' => '<span class="cf-from cf-meta">Van</span> <span class="cf-date">23/9/22</span> <span class="cf-to cf-meta">tot</span> <span class="cf-date">7/10/22</span>',
                'contentType' => ContentType::html(),
                'format' => Format::xs(),
            ],
            [
                'result' => '<span class="cf-from cf-meta">Van</span> ' .
                    '<span class="cf-date">23 september 2022</span> ' .
                    '<span class="cf-to cf-meta">tot</span> ' .
                    '<span class="cf-date">7 oktober 2022</span>',
                'contentType' => ContentType::html(),
                'format' => Format::sm(),
            ],
            [
                'result' => '<ul class="cnw-event-date-info">' .
                    '<li><span class="cf-weekday cf-meta">Vrijdag</span> ' .
                    '<span class="cf-date">23 september 2022</span></li>' .
                    '<li><span class="cf-weekday cf-meta">Vrijdag</span> ' .
                    '<span class="cf-date">30 september 2022</span></li>' .
                    '<li><span class="cf-weekday cf-meta">Vrijdag</span> ' .
                    '<span class="cf-date">7 oktober 2022</span></li></ul>',
                'contentType' => ContentType::html(),
                'format' => Format::md(),
            ],
            [
                'result' => '<ul class="cnw-event-date-info">' .
                    '<li><time itemprop="startDate" datetime="2022-09-23T18:00:00+00:00">' .
                    '<span class="cf-weekday cf-meta">Vrijdag</span> ' .
                    '<span class="cf-date">23 september 2022</span> ' .
                    '<span class="cf-from cf-meta">van</span> ' .
                    '<span class="cf-time">20:00</span></time> ' .
                    '<span class="cf-to cf-meta">tot</span> ' .
                    '<time itemprop="endDate" datetime="2022-09-23T21:00:00+00:00">' .
                    '<span class="cf-time">23:00</span></time></li>' .
                    '<li><time itemprop="startDate" datetime="2022-09-30T18:00:00+00:00">' .
                    '<span class="cf-weekday cf-meta">Vrijdag</span> ' .
                    '<span class="cf-date">30 september 2022</span> ' .
                    '<span class="cf-from cf-meta">van</span> ' .
                    '<span class="cf-time">20:00</span></time> ' .
                    '<span class="cf-to cf-meta">tot</span> ' .
                    '<time itemprop="endDate" datetime="2022-09-30T21:00:00+00:00">' .
                    '<span class="cf-time">23:00</span></time></li>' .
                    '<li><time itemprop="startDate" datetime="2022-10-06T22:00:00+00:00">' .
                    '<span class="cf-weekday cf-meta">Vrijdag</span> ' .
                    '<span class="cf-date">7 oktober 2022</span></time></li></ul>',
                'contentType' => ContentType::html(),
                'format' => Format::lg(),
            ],
        ];
    }
}
