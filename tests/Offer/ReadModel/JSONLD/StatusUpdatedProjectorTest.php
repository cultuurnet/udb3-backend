<?php declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\ReadModel\JSONLD;

use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\Event\ValueObjects\Status;
use CultuurNet\UDB3\Event\ValueObjects\StatusReason;
use CultuurNet\UDB3\Event\ValueObjects\StatusType;
use CultuurNet\UDB3\EventSourcing\DomainMessageBuilder;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Offer\Events\StatusUpdated;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\Timestamp;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class StatusUpdatedProjectorTest extends TestCase
{
    /**
     * @var DomainMessageBuilder
     */
    private $domainMessageBuilder;

    /**
     * @var DocumentRepository
     */
    private $eventRepository;

    /**
     * @var DocumentRepository
     */
    private $placeRepository;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var StatusUpdatedProjector
     */
    private $statusUpdatedProjector;

    protected function setUp(): void
    {
        $this->domainMessageBuilder = new DomainMessageBuilder();

        $this->eventRepository = new InMemoryDocumentRepository();
        $this->placeRepository = new InMemoryDocumentRepository();
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->statusUpdatedProjector = new StatusUpdatedProjector(
            $this->eventRepository,
            $this->placeRepository,
            $this->logger
        );
    }

    /**
     * @test
     */
    public function it_requires_an_existing_event_or_place(): void
    {
        $this->logger->expects($this->once())
            ->method('warning')
            ->with('No place or event found with id 542d8328-0051-4890-afbb-38b0cc8dae07 to apply StatusUpdated.');

        $statusUpdated = new StatusUpdated(
            '542d8328-0051-4890-afbb-38b0cc8dae07',
            new Status(
                StatusType::unavailable(),
                [
                    new StatusReason(new Language('nl'), 'Nog steeds geen concerten mogelijk.'),
                    new StatusReason(new Language('en'), 'Still no concerts allowed.'),
                ]
            )
        );

        $this->statusUpdatedProjector->handle(
            $this->domainMessageBuilder->create($statusUpdated)
        );
    }

    /**
     * @test
     * @dataProvider calendarProvider
     */
    public function it_applies_status_updated_on_events(Calendar $calendar, array $expectedBody): void
    {
        $initialEvent = new JsonDocument('542d8328-0051-4890-afbb-38b0cc8dae07');
        $initialEvent = $initialEvent->withAssocBody($calendar->toJsonLd());
        $this->eventRepository->save($initialEvent);

        $statusUpdated = new StatusUpdated(
            '542d8328-0051-4890-afbb-38b0cc8dae07',
            new Status(
                StatusType::unavailable(),
                [
                    new StatusReason(new Language('nl'), 'Nog steeds geen concerten mogelijk.'),
                    new StatusReason(new Language('en'), 'Still no concerts allowed.'),
                ]
            )
        );
        $this->statusUpdatedProjector->handle(
            $this->domainMessageBuilder->create($statusUpdated)
        );

        $actualDocument = $this->eventRepository->fetch('542d8328-0051-4890-afbb-38b0cc8dae07');
        $this->assertEquals($expectedBody, $actualDocument->getAssocBody());
    }

    public function calendarProvider(): array
    {
        return [
            'permanent' => [
                new Calendar(
                    CalendarType::PERMANENT()
                ),
                [
                    'status' => [
                        'type' => 'Unavailable',
                        'reason' => [
                            'nl' => 'Nog steeds geen concerten mogelijk.',
                            'en' => 'Still no concerts allowed.',
                        ],
                    ],
                    'calendarType' => 'permanent',
                ],
            ],
            'periodic' => [
                new Calendar(
                    CalendarType::PERIODIC(),
                    new \DateTime('2020-12-01'),
                    new \DateTime('2020-12-08')
                ),
                [
                    'status' => [
                        'type' => 'Unavailable',
                        'reason' => [
                            'nl' => 'Nog steeds geen concerten mogelijk.',
                            'en' => 'Still no concerts allowed.',
                        ],
                    ],
                    'calendarType' => 'periodic',
                    'startDate' => '2020-12-01T00:00:00+00:00',
                    'endDate' => '2020-12-08T00:00:00+00:00',
                ],
            ],
            'single' => [
                new Calendar(
                    CalendarType::SINGLE(),
                    new \DateTime('2020-12-01'),
                    new \DateTime('2020-12-08'),
                    [
                        new Timestamp(new \DateTime('2020-12-01'), new \DateTime('2020-12-08')),
                    ]
                ),
                [
                    'status' => [
                        'type' => 'Unavailable',
                        'reason' => [
                            'nl' => 'Nog steeds geen concerten mogelijk.',
                            'en' => 'Still no concerts allowed.',
                        ],
                    ],
                    'calendarType' => 'single',
                    'startDate' => '2020-12-01T00:00:00+00:00',
                    'endDate' => '2020-12-08T00:00:00+00:00',
                    'subEvent' => [
                        [
                            'status' => [
                                'type' => 'Unavailable',
                                'reason' => [
                                    'nl' => 'Nog steeds geen concerten mogelijk.',
                                    'en' => 'Still no concerts allowed.',
                                ],
                            ],
                            'startDate' => '2020-12-01T00:00:00+00:00',
                            'endDate' => '2020-12-08T00:00:00+00:00',
                            '@type' => 'Event',
                        ],
                    ]
                ],
            ],
            'multiple' => [
                new Calendar(
                    CalendarType::SINGLE(),
                    new \DateTime('2020-12-01'),
                    new \DateTime('2020-12-08'),
                    [
                        new Timestamp(new \DateTime('2020-12-01'), new \DateTime('2020-12-04')),
                        new Timestamp(new \DateTime('2020-12-06'), new \DateTime('2020-12-08')),
                    ]
                ),
                [
                    'status' => [
                        'type' => 'Unavailable',
                        'reason' => [
                            'nl' => 'Nog steeds geen concerten mogelijk.',
                            'en' => 'Still no concerts allowed.',
                        ],
                    ],
                    'calendarType' => 'single',
                    'startDate' => '2020-12-01T00:00:00+00:00',
                    'endDate' => '2020-12-08T00:00:00+00:00',
                    'subEvent' => [
                        [
                            'status' => [
                                'type' => 'Unavailable',
                                'reason' => [
                                    'nl' => 'Nog steeds geen concerten mogelijk.',
                                    'en' => 'Still no concerts allowed.',
                                ],
                            ],
                            'startDate' => '2020-12-01T00:00:00+00:00',
                            'endDate' => '2020-12-04T00:00:00+00:00',
                            '@type' => 'Event',
                        ],
                        [
                            'status' => [
                                'type' => 'Unavailable',
                                'reason' => [
                                    'nl' => 'Nog steeds geen concerten mogelijk.',
                                    'en' => 'Still no concerts allowed.',
                                ],
                            ],
                            'startDate' => '2020-12-06T00:00:00+00:00',
                            'endDate' => '2020-12-08T00:00:00+00:00',
                            '@type' => 'Event',
                        ],
                    ]
                ],
            ],
        ];
    }
}
