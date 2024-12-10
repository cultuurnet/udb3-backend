<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\Event;

use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Model\Event\ImmutableEvent;
use CultuurNet\UDB3\Model\Organizer\OrganizerReference;
use CultuurNet\UDB3\Model\Place\ImmutablePlace;
use CultuurNet\UDB3\Model\Place\PlaceReference;
use CultuurNet\UDB3\Model\Serializer\ValueObject\MediaObject\VideoDenormalizer;
use CultuurNet\UDB3\Model\ValueObject\Audience\Age;
use CultuurNet\UDB3\Model\ValueObject\Audience\AgeRange;
use CultuurNet\UDB3\Model\ValueObject\Audience\AudienceType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailability;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailabilityType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\DateRange;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvent;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvents;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Status;
use CultuurNet\UDB3\Model\ValueObject\Calendar\StatusReason;
use CultuurNet\UDB3\Model\ValueObject\Calendar\StatusType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\MultipleSubEventsCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Day;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Days;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Hour;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Minute;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHour;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Time;
use CultuurNet\UDB3\Model\ValueObject\Calendar\PeriodicCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\PermanentCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SingleSubEventCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\TranslatedStatusReason;
use CultuurNet\UDB3\Model\ValueObject\Contact\BookingAvailability as ContactBookingAvailability;
use CultuurNet\UDB3\Model\ValueObject\Contact\BookingInfo;
use CultuurNet\UDB3\Model\ValueObject\Contact\ContactPoint;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumber;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumbers;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\MediaObject;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\MediaObjectReference;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\MediaObjectReferences;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\MediaObjectType;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\Video;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\VideoCollection;
use CultuurNet\UDB3\Model\ValueObject\Moderation\WorkflowStatus;
use CultuurNet\UDB3\Model\ValueObject\Price\PriceInfo;
use CultuurNet\UDB3\Model\ValueObject\Price\Tariff;
use CultuurNet\UDB3\Model\ValueObject\Price\TariffName;
use CultuurNet\UDB3\Model\ValueObject\Price\Tariffs;
use CultuurNet\UDB3\Model\ValueObject\Price\TranslatedTariffName;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Categories;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use CultuurNet\UDB3\Model\ValueObject\Text\Description;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use CultuurNet\UDB3\Model\ValueObject\Text\TranslatedDescription;
use CultuurNet\UDB3\Model\ValueObject\Text\TranslatedTitle;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Online\AttendanceMode;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddresses;
use CultuurNet\UDB3\Model\ValueObject\Web\InvalidUrl;
use CultuurNet\UDB3\Model\ValueObject\Web\TranslatedWebsiteLabel;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Model\ValueObject\Web\Urls;
use CultuurNet\UDB3\Model\ValueObject\Web\WebsiteLabel;
use CultuurNet\UDB3\Model\ValueObject\Web\WebsiteLink;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\UuidFactoryInterface;
use Symfony\Component\Serializer\Exception\UnsupportedException;

class EventDenormalizerTest extends TestCase
{
    private EventDenormalizer $denormalizer;

    /**
     * @var UuidFactoryInterface&MockObject
     */
    private $uuidFactory;

    public function setUp(): void
    {
        $this->uuidFactory = $this->createMock(UuidFactoryInterface::class);

        $this->denormalizer = new EventDenormalizer(
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            new VideoDenormalizer($this->uuidFactory)
        );
    }

    /**
     * @test
     */
    public function it_should_denormalize_event_data_with_only_the_required_properties(): void
    {
        $eventData = [
            '@id' => 'https://io.uitdatabank.be/event/9f34efc7-a528-4ea8-a53e-a183f21abbab',
            '@type' => 'Event',
            '@context' => '/contexts/event',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Titel voorbeeld',
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.be/place/dbe91250-4e4b-495c-b692-3da9563b0d52',
            ],
            'calendarType' => 'permanent',
            'terms' => [
                [
                    'id' => '0.50.1.0.1',
                ],
            ],
        ];

        $expected = new ImmutableEvent(
            new Uuid('9f34efc7-a528-4ea8-a53e-a183f21abbab'),
            new Language('nl'),
            new TranslatedTitle(
                new Language('nl'),
                new Title('Titel voorbeeld')
            ),
            new PermanentCalendar(new OpeningHours()),
            PlaceReference::createWithPlaceId(new Uuid('dbe91250-4e4b-495c-b692-3da9563b0d52')),
            new Categories(
                new Category(
                    new CategoryID('0.50.1.0.1')
                )
            )
        );

        $actual = $this->denormalizer->denormalize($eventData, ImmutableEvent::class);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_has_default_main_language_nl(): void
    {
        $eventData = [
            '@id' => 'https://io.uitdatabank.be/event/9f34efc7-a528-4ea8-a53e-a183f21abbab',
            '@type' => 'Event',
            '@context' => '/contexts/event',
            'name' => [
                'nl' => 'Titel voorbeeld',
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.be/place/dbe91250-4e4b-495c-b692-3da9563b0d52',
            ],
            'calendarType' => 'permanent',
            'terms' => [
                [
                    'id' => '0.50.1.0.1',
                ],
            ],
        ];

        $expected = new ImmutableEvent(
            new Uuid('9f34efc7-a528-4ea8-a53e-a183f21abbab'),
            new Language('nl'),
            new TranslatedTitle(
                new Language('nl'),
                new Title('Titel voorbeeld')
            ),
            new PermanentCalendar(new OpeningHours()),
            PlaceReference::createWithPlaceId(new Uuid('dbe91250-4e4b-495c-b692-3da9563b0d52')),
            new Categories(
                new Category(
                    new CategoryID('0.50.1.0.1')
                )
            )
        );

        $actual = $this->denormalizer->denormalize($eventData, ImmutableEvent::class);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_denormalize_event_data_with_status(): void
    {
        $eventData = [
            '@id' => 'https://io.uitdatabank.be/event/9f34efc7-a528-4ea8-a53e-a183f21abbab',
            '@type' => 'Event',
            '@context' => '/contexts/event',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Titel voorbeeld',
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.be/place/dbe91250-4e4b-495c-b692-3da9563b0d52',
            ],
            'status' => [
                'type' => 'Unavailable',
                'reason' => [
                    'nl' => 'Nederlandse reden',
                    'fr' => 'Franse reden',
                ],
            ],
            'calendarType' => 'permanent',
            'terms' => [
                [
                    'id' => '0.50.1.0.1',
                ],
            ],
        ];

        $expectedCalendar = (new PermanentCalendar(new OpeningHours()))->withStatus(
            new Status(
                StatusType::Unavailable(),
                (new TranslatedStatusReason(
                    new Language('nl'),
                    new StatusReason('Nederlandse reden')
                ))
                    ->withTranslation(new Language('fr'), new StatusReason('Franse reden'))
            )
        );

        $expected = new ImmutableEvent(
            new Uuid('9f34efc7-a528-4ea8-a53e-a183f21abbab'),
            new Language('nl'),
            new TranslatedTitle(
                new Language('nl'),
                new Title('Titel voorbeeld')
            ),
            $expectedCalendar,
            PlaceReference::createWithPlaceId(new Uuid('dbe91250-4e4b-495c-b692-3da9563b0d52')),
            new Categories(
                new Category(
                    new CategoryID('0.50.1.0.1')
                )
            )
        );

        $actual = $this->denormalizer->denormalize($eventData, ImmutableEvent::class);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_denormalize_event_data_with_status_without_reason(): void
    {
        $eventData = [
            '@id' => 'https://io.uitdatabank.be/event/9f34efc7-a528-4ea8-a53e-a183f21abbab',
            '@type' => 'Event',
            '@context' => '/contexts/event',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Titel voorbeeld',
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.be/place/dbe91250-4e4b-495c-b692-3da9563b0d52',
            ],
            'status' => [
                'type' => 'Available',
            ],
            'calendarType' => 'permanent',
            'terms' => [
                [
                    'id' => '0.50.1.0.1',
                ],
            ],
        ];

        $expectedCalendar = (new PermanentCalendar(new OpeningHours()))->withStatus(
            new Status(StatusType::Available())
        );

        $expected = new ImmutableEvent(
            new Uuid('9f34efc7-a528-4ea8-a53e-a183f21abbab'),
            new Language('nl'),
            new TranslatedTitle(
                new Language('nl'),
                new Title('Titel voorbeeld')
            ),
            $expectedCalendar,
            PlaceReference::createWithPlaceId(new Uuid('dbe91250-4e4b-495c-b692-3da9563b0d52')),
            new Categories(
                new Category(
                    new CategoryID('0.50.1.0.1')
                )
            )
        );

        $actual = $this->denormalizer->denormalize($eventData, ImmutableEvent::class);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_denormalize_event_data_with_title_translations(): void
    {
        $eventData = [
            '@id' => 'https://io.uitdatabank.be/event/9f34efc7-a528-4ea8-a53e-a183f21abbab',
            '@type' => 'Event',
            '@context' => '/contexts/event',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Titel voorbeeld',
                'en' => 'Example title',
                'fr' => 'Titre de l\'exemple',
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.be/place/dbe91250-4e4b-495c-b692-3da9563b0d52',
            ],
            'calendarType' => 'permanent',
            'terms' => [
                [
                    'id' => '0.50.1.0.1',
                ],
            ],
        ];

        $expected = new ImmutableEvent(
            new Uuid('9f34efc7-a528-4ea8-a53e-a183f21abbab'),
            new Language('nl'),
            (new TranslatedTitle(
                new Language('nl'),
                new Title('Titel voorbeeld')
            ))
                ->withTranslation(new Language('en'), new Title('Example title'))
                ->withTranslation(new Language('fr'), new Title('Titre de l\'exemple')),
            new PermanentCalendar(new OpeningHours()),
            PlaceReference::createWithPlaceId(new Uuid('dbe91250-4e4b-495c-b692-3da9563b0d52')),
            new Categories(
                new Category(
                    new CategoryID('0.50.1.0.1')
                )
            )
        );

        $actual = $this->denormalizer->denormalize($eventData, ImmutableEvent::class);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_denormalize_event_data_with_a_single_date_range_calendar(): void
    {
        $eventData = [
            '@id' => 'https://io.uitdatabank.be/event/9f34efc7-a528-4ea8-a53e-a183f21abbab',
            '@type' => 'Event',
            '@context' => '/contexts/event',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Titel voorbeeld',
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.be/place/dbe91250-4e4b-495c-b692-3da9563b0d52',
            ],
            'calendarType' => 'single',
            'startDate' => '2018-01-01T13:00:00+01:00',
            'endDate' => '2018-01-01T17:00:00+01:00',
            'subEvent' => [
                [
                    '@type' => 'Event',
                    'startDate' => '2018-01-01T13:00:00+01:00',
                    'endDate' => '2018-01-01T17:00:00+01:00',
                ],
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.1',
                ],
            ],
        ];

        $expected = new ImmutableEvent(
            new Uuid('9f34efc7-a528-4ea8-a53e-a183f21abbab'),
            new Language('nl'),
            new TranslatedTitle(
                new Language('nl'),
                new Title('Titel voorbeeld')
            ),
            new SingleSubEventCalendar(
                new SubEvent(
                    new DateRange(
                        DateTimeFactory::fromAtom('2018-01-01T13:00:00+01:00'),
                        DateTimeFactory::fromAtom('2018-01-01T17:00:00+01:00')
                    ),
                    new Status(
                        StatusType::Available()
                    ),
                    new BookingAvailability(BookingAvailabilityType::Available())
                ),
            ),
            PlaceReference::createWithPlaceId(new Uuid('dbe91250-4e4b-495c-b692-3da9563b0d52')),
            new Categories(
                new Category(
                    new CategoryID('0.50.1.0.1')
                )
            )
        );

        $actual = $this->denormalizer->denormalize($eventData, ImmutableEvent::class);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_denormalize_event_data_with_a_single_date_range_calendar_and_status_and_reason(): void
    {
        $eventData = [
            '@id' => 'https://io.uitdatabank.be/event/9f34efc7-a528-4ea8-a53e-a183f21abbab',
            '@type' => 'Event',
            '@context' => '/contexts/event',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Titel voorbeeld',
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.be/place/dbe91250-4e4b-495c-b692-3da9563b0d52',
            ],
            'calendarType' => 'single',
            'startDate' => '2018-01-01T13:00:00+01:00',
            'endDate' => '2018-01-01T17:00:00+01:00',
            'subEvent' => [
                [
                    '@type' => 'Event',
                    'startDate' => '2018-01-01T13:00:00+01:00',
                    'endDate' => '2018-01-01T17:00:00+01:00',
                    'status' => [
                        'type' => 'Unavailable',
                        'reason' => [
                            'nl' => 'Nederlandse reden',
                            'fr' => 'Franse reden',
                        ],
                    ],
                ],
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.1',
                ],
            ],
        ];

        $expected = new ImmutableEvent(
            new Uuid('9f34efc7-a528-4ea8-a53e-a183f21abbab'),
            new Language('nl'),
            new TranslatedTitle(
                new Language('nl'),
                new Title('Titel voorbeeld')
            ),
            new SingleSubEventCalendar(
                new SubEvent(
                    new DateRange(
                        DateTimeFactory::fromAtom('2018-01-01T13:00:00+01:00'),
                        DateTimeFactory::fromAtom('2018-01-01T17:00:00+01:00')
                    ),
                    new Status(
                        StatusType::Unavailable(),
                        (new TranslatedStatusReason(
                            new Language('nl'),
                            new StatusReason('Nederlandse reden')
                        ))
                            ->withTranslation(new Language('fr'), new StatusReason('Franse reden'))
                    ),
                    new BookingAvailability(BookingAvailabilityType::Available())
                )
            ),
            PlaceReference::createWithPlaceId(new Uuid('dbe91250-4e4b-495c-b692-3da9563b0d52')),
            new Categories(
                new Category(
                    new CategoryID('0.50.1.0.1')
                )
            )
        );

        $actual = $this->denormalizer->denormalize($eventData, ImmutableEvent::class);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_denormalize_event_data_with_a_single_date_range_calendar_and_no_sub_event(): void
    {
        $eventData = [
            '@id' => 'https://io.uitdatabank.be/event/9f34efc7-a528-4ea8-a53e-a183f21abbab',
            '@type' => 'Event',
            '@context' => '/contexts/event',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Titel voorbeeld',
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.be/place/dbe91250-4e4b-495c-b692-3da9563b0d52',
            ],
            'calendarType' => 'single',
            'startDate' => '2018-01-01T13:00:00+01:00',
            'endDate' => '2018-01-01T17:00:00+01:00',
            'terms' => [
                [
                    'id' => '0.50.1.0.1',
                ],
            ],
        ];

        $expected = new ImmutableEvent(
            new Uuid('9f34efc7-a528-4ea8-a53e-a183f21abbab'),
            new Language('nl'),
            new TranslatedTitle(
                new Language('nl'),
                new Title('Titel voorbeeld')
            ),
            new SingleSubEventCalendar(
                new SubEvent(
                    new DateRange(
                        DateTimeFactory::fromAtom('2018-01-01T13:00:00+01:00'),
                        DateTimeFactory::fromAtom('2018-01-01T17:00:00+01:00')
                    ),
                    new Status(
                        StatusType::Available()
                    ),
                    new BookingAvailability(BookingAvailabilityType::Available())
                )
            ),
            PlaceReference::createWithPlaceId(new Uuid('dbe91250-4e4b-495c-b692-3da9563b0d52')),
            new Categories(
                new Category(
                    new CategoryID('0.50.1.0.1')
                )
            )
        );

        $actual = $this->denormalizer->denormalize($eventData, ImmutableEvent::class);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_denormalize_event_data_with_a_multiple_date_ranges_calendar(): void
    {
        $eventData = [
            '@id' => 'https://io.uitdatabank.be/event/9f34efc7-a528-4ea8-a53e-a183f21abbab',
            '@type' => 'Event',
            '@context' => '/contexts/event',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Titel voorbeeld',
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.be/place/dbe91250-4e4b-495c-b692-3da9563b0d52',
            ],
            'calendarType' => 'multiple',
            'startDate' => '2018-01-01T13:00:00+01:00',
            'endDate' => '2018-01-10T17:00:00+01:00',
            'subEvent' => [
                [
                    '@type' => 'Event',
                    'startDate' => '2018-01-01T13:00:00+01:00',
                    'endDate' => '2018-01-01T17:00:00+01:00',
                ],
                [
                    '@type' => 'Event',
                    'startDate' => '2018-01-03T13:00:00+01:00',
                    'endDate' => '2018-01-03T17:00:00+01:00',
                ],
                [
                    '@type' => 'Event',
                    'startDate' => '2018-01-10T13:00:00+01:00',
                    'endDate' => '2018-01-10T17:00:00+01:00',
                ],
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.1',
                ],
            ],
        ];

        $expected = new ImmutableEvent(
            new Uuid('9f34efc7-a528-4ea8-a53e-a183f21abbab'),
            new Language('nl'),
            new TranslatedTitle(
                new Language('nl'),
                new Title('Titel voorbeeld')
            ),
            new MultipleSubEventsCalendar(
                new SubEvents(
                    new SubEvent(
                        new DateRange(
                            DateTimeFactory::fromAtom('2018-01-01T13:00:00+01:00'),
                            DateTimeFactory::fromAtom('2018-01-01T17:00:00+01:00')
                        ),
                        new Status(
                            StatusType::Available()
                        ),
                        new BookingAvailability(BookingAvailabilityType::Available())
                    ),
                    new SubEvent(
                        new DateRange(
                            DateTimeFactory::fromAtom('2018-01-03T13:00:00+01:00'),
                            DateTimeFactory::fromAtom('2018-01-03T17:00:00+01:00')
                        ),
                        new Status(
                            StatusType::Available()
                        ),
                        new BookingAvailability(BookingAvailabilityType::Available())
                    ),
                    new SubEvent(
                        new DateRange(
                            DateTimeFactory::fromAtom('2018-01-10T13:00:00+01:00'),
                            DateTimeFactory::fromAtom('2018-01-10T17:00:00+01:00')
                        ),
                        new Status(
                            StatusType::Available()
                        ),
                        new BookingAvailability(BookingAvailabilityType::Available())
                    )
                )
            ),
            PlaceReference::createWithPlaceId(new Uuid('dbe91250-4e4b-495c-b692-3da9563b0d52')),
            new Categories(
                new Category(
                    new CategoryID('0.50.1.0.1')
                )
            )
        );

        $actual = $this->denormalizer->denormalize($eventData, ImmutableEvent::class);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_denormalize_event_data_with_a_multiple_date_ranges_calendar_and_status_and_reason(): void
    {
        $eventData = [
            '@id' => 'https://io.uitdatabank.be/event/9f34efc7-a528-4ea8-a53e-a183f21abbab',
            '@type' => 'Event',
            '@context' => '/contexts/event',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Titel voorbeeld',
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.be/place/dbe91250-4e4b-495c-b692-3da9563b0d52',
            ],
            'calendarType' => 'multiple',
            'startDate' => '2018-01-01T13:00:00+01:00',
            'endDate' => '2018-01-10T17:00:00+01:00',
            'subEvent' => [
                [
                    '@type' => 'Event',
                    'startDate' => '2018-01-01T13:00:00+01:00',
                    'endDate' => '2018-01-01T17:00:00+01:00',
                    'status' => [
                        'type' => 'Unavailable',
                        'reason' => [
                            'nl' => 'Nederlandse reden',
                            'fr' => 'Franse reden',
                        ],
                    ],
                ],
                [
                    '@type' => 'Event',
                    'startDate' => '2018-01-03T13:00:00+01:00',
                    'endDate' => '2018-01-03T17:00:00+01:00',
                    'status' => [
                        'type' => 'Available',
                    ],
                ],
                [
                    '@type' => 'Event',
                    'startDate' => '2018-01-10T13:00:00+01:00',
                    'endDate' => '2018-01-10T17:00:00+01:00',
                    'status' => [
                        'type' => 'TemporarilyUnavailable',
                    ],
                ],
                [
                    '@type' => 'Event',
                    'startDate' => '2018-01-10T13:00:00+01:00',
                    'endDate' => '2018-01-10T17:00:00+01:00',
                    'status' => [
                        'reason' => [
                            'nl' => 'Nederlandse reden zonder status type',
                            'fr' => 'Franse reden zonder status type',
                        ],
                    ],
                ],
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.1',
                ],
            ],
        ];

        $expected = new ImmutableEvent(
            new Uuid('9f34efc7-a528-4ea8-a53e-a183f21abbab'),
            new Language('nl'),
            new TranslatedTitle(
                new Language('nl'),
                new Title('Titel voorbeeld')
            ),
            new MultipleSubEventsCalendar(
                new SubEvents(
                    new SubEvent(
                        new DateRange(
                            DateTimeFactory::fromAtom('2018-01-01T13:00:00+01:00'),
                            DateTimeFactory::fromAtom('2018-01-01T17:00:00+01:00')
                        ),
                        new Status(
                            StatusType::Unavailable(),
                            (new TranslatedStatusReason(
                                new Language('nl'),
                                new StatusReason('Nederlandse reden')
                            ))
                                ->withTranslation(new Language('fr'), new StatusReason('Franse reden'))
                        ),
                        new BookingAvailability(BookingAvailabilityType::Available())
                    ),
                    new SubEvent(
                        new DateRange(
                            DateTimeFactory::fromAtom('2018-01-03T13:00:00+01:00'),
                            DateTimeFactory::fromAtom('2018-01-03T17:00:00+01:00')
                        ),
                        new Status(
                            StatusType::Available()
                        ),
                        new BookingAvailability(BookingAvailabilityType::Available())
                    ),
                    new SubEvent(
                        new DateRange(
                            DateTimeFactory::fromAtom('2018-01-10T13:00:00+01:00'),
                            DateTimeFactory::fromAtom('2018-01-10T17:00:00+01:00')
                        ),
                        new Status(
                            StatusType::TemporarilyUnavailable()
                        ),
                        new BookingAvailability(BookingAvailabilityType::Available())
                    ),
                    new SubEvent(
                        new DateRange(
                            DateTimeFactory::fromAtom('2018-01-10T13:00:00+01:00'),
                            DateTimeFactory::fromAtom('2018-01-10T17:00:00+01:00')
                        ),
                        new Status(
                            StatusType::Available(),
                            (new TranslatedStatusReason(
                                new Language('nl'),
                                new StatusReason('Nederlandse reden zonder status type')
                            ))
                                ->withTranslation(
                                    new Language('fr'),
                                    new StatusReason('Franse reden zonder status type')
                                )
                        ),
                        new BookingAvailability(BookingAvailabilityType::Available())
                    )
                )
            ),
            PlaceReference::createWithPlaceId(new Uuid('dbe91250-4e4b-495c-b692-3da9563b0d52')),
            new Categories(
                new Category(
                    new CategoryID('0.50.1.0.1')
                )
            )
        );

        $actual = $this->denormalizer->denormalize($eventData, ImmutableEvent::class);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_can_denormalize_booking_availability(): void
    {
        $eventData = [
            '@id' => 'https://io.uitdatabank.be/event/9f34efc7-a528-4ea8-a53e-a183f21abbab',
            '@type' => 'Event',
            '@context' => '/contexts/event',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Titel voorbeeld',
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.be/place/dbe91250-4e4b-495c-b692-3da9563b0d52',
            ],
            'calendarType' => 'multiple',
            'startDate' => '2018-01-01T13:00:00+01:00',
            'endDate' => '2018-01-10T17:00:00+01:00',
            'subEvent' => [
                [
                    '@type' => 'Event',
                    'startDate' => '2018-01-01T13:00:00+01:00',
                    'endDate' => '2018-01-01T17:00:00+01:00',
                    'bookingAvailability' => [
                        'type' => 'Unavailable',
                    ],
                ],
                [
                    '@type' => 'Event',
                    'startDate' => '2018-01-03T13:00:00+01:00',
                    'endDate' => '2018-01-03T17:00:00+01:00',
                    'bookingAvailability' => [
                        'type' => 'Unavailable',
                    ],
                ],
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.1',
                ],
            ],
        ];

        $expected = new ImmutableEvent(
            new Uuid('9f34efc7-a528-4ea8-a53e-a183f21abbab'),
            new Language('nl'),
            new TranslatedTitle(
                new Language('nl'),
                new Title('Titel voorbeeld')
            ),
            new MultipleSubEventsCalendar(
                new SubEvents(
                    new SubEvent(
                        new DateRange(
                            DateTimeFactory::fromAtom('2018-01-01T13:00:00+01:00'),
                            DateTimeFactory::fromAtom('2018-01-01T17:00:00+01:00')
                        ),
                        new Status(StatusType::Available()),
                        new BookingAvailability(BookingAvailabilityType::Unavailable())
                    ),
                    new SubEvent(
                        new DateRange(
                            DateTimeFactory::fromAtom('2018-01-03T13:00:00+01:00'),
                            DateTimeFactory::fromAtom('2018-01-03T17:00:00+01:00')
                        ),
                        new Status(StatusType::Available()),
                        new BookingAvailability(BookingAvailabilityType::Unavailable())
                    )
                )
            ),
            PlaceReference::createWithPlaceId(new Uuid('dbe91250-4e4b-495c-b692-3da9563b0d52')),
            new Categories(
                new Category(
                    new CategoryID('0.50.1.0.1')
                )
            )
        );

        $actual = $this->denormalizer->denormalize($eventData, ImmutableEvent::class);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_denormalize_event_data_with_single_date_range_and_apply_status_to_sub_event_with_no_status(): void
    {
        $eventData = [
            '@id' => 'https://io.uitdatabank.be/event/9f34efc7-a528-4ea8-a53e-a183f21abbab',
            '@type' => 'Event',
            '@context' => '/contexts/event',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Titel voorbeeld',
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.be/place/dbe91250-4e4b-495c-b692-3da9563b0d52',
            ],
            'calendarType' => 'single',
            'status' => [
                'type' => 'TemporarilyUnavailable',
                'reason' => [
                    'nl' => 'Nederlands',
                    'fr' => 'Frans',
                ],
            ],
            'startDate' => '2018-01-01T13:00:00+01:00',
            'endDate' => '2018-01-01T17:00:00+01:00',
            'terms' => [
                [
                    'id' => '0.50.1.0.1',
                ],
            ],
        ];

        $expected = new ImmutableEvent(
            new Uuid('9f34efc7-a528-4ea8-a53e-a183f21abbab'),
            new Language('nl'),
            new TranslatedTitle(
                new Language('nl'),
                new Title('Titel voorbeeld')
            ),
            (new SingleSubEventCalendar(
                new SubEvent(
                    new DateRange(
                        DateTimeFactory::fromAtom('2018-01-01T13:00:00+01:00'),
                        DateTimeFactory::fromAtom('2018-01-01T17:00:00+01:00')
                    ),
                    new Status(
                        StatusType::TemporarilyUnavailable(),
                        (new TranslatedStatusReason(
                            new Language('nl'),
                            new StatusReason('Nederlands')
                        ))->withTranslation(new Language('fr'), new StatusReason('Frans'))
                    ),
                    new BookingAvailability(BookingAvailabilityType::Available())
                )
            ))->withStatus(
                new Status(
                    StatusType::TemporarilyUnavailable(),
                    (new TranslatedStatusReason(
                        new Language('nl'),
                        new StatusReason('Nederlands')
                    ))->withTranslation(new Language('fr'), new StatusReason('Frans'))
                )
            ),
            PlaceReference::createWithPlaceId(new Uuid('dbe91250-4e4b-495c-b692-3da9563b0d52')),
            new Categories(
                new Category(
                    new CategoryID('0.50.1.0.1')
                )
            )
        );

        $actual = $this->denormalizer->denormalize($eventData, ImmutableEvent::class);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_denormalize_event_data_with_single_sub_event_and_apply_status_to_sub_event_with_no_status(): void
    {
        $eventData = [
            '@id' => 'https://io.uitdatabank.be/event/9f34efc7-a528-4ea8-a53e-a183f21abbab',
            '@type' => 'Event',
            '@context' => '/contexts/event',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Titel voorbeeld',
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.be/place/dbe91250-4e4b-495c-b692-3da9563b0d52',
            ],
            'calendarType' => 'single',
            'status' => [
                'type' => 'TemporarilyUnavailable',
                'reason' => [
                    'nl' => 'Nederlands',
                    'fr' => 'Frans',
                ],
            ],
            'startDate' => '2018-01-01T13:00:00+01:00',
            'endDate' => '2018-01-01T17:00:00+01:00',
            'subEvent' => [
                [
                    '@type' => 'Event',
                    'startDate' => '2018-01-01T13:00:00+01:00',
                    'endDate' => '2018-01-01T17:00:00+01:00',
                ],
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.1',
                ],
            ],
        ];

        $expected = new ImmutableEvent(
            new Uuid('9f34efc7-a528-4ea8-a53e-a183f21abbab'),
            new Language('nl'),
            new TranslatedTitle(
                new Language('nl'),
                new Title('Titel voorbeeld')
            ),
            (new SingleSubEventCalendar(
                new SubEvent(
                    new DateRange(
                        DateTimeFactory::fromAtom('2018-01-01T13:00:00+01:00'),
                        DateTimeFactory::fromAtom('2018-01-01T17:00:00+01:00')
                    ),
                    new Status(
                        StatusType::TemporarilyUnavailable(),
                        (new TranslatedStatusReason(
                            new Language('nl'),
                            new StatusReason('Nederlands')
                        ))->withTranslation(new Language('fr'), new StatusReason('Frans'))
                    ),
                    new BookingAvailability(BookingAvailabilityType::Available())
                )
            ))->withStatus(
                new Status(
                    StatusType::TemporarilyUnavailable(),
                    (new TranslatedStatusReason(
                        new Language('nl'),
                        new StatusReason('Nederlands')
                    ))->withTranslation(new Language('fr'), new StatusReason('Frans'))
                )
            ),
            PlaceReference::createWithPlaceId(new Uuid('dbe91250-4e4b-495c-b692-3da9563b0d52')),
            new Categories(
                new Category(
                    new CategoryID('0.50.1.0.1')
                )
            )
        );

        $actual = $this->denormalizer->denormalize($eventData, ImmutableEvent::class);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_denormalize_event_data_with_multiple_date_ranges_and_apply_status_to_sub_events_with_no_status(): void
    {
        $eventData = [
            '@id' => 'https://io.uitdatabank.be/event/9f34efc7-a528-4ea8-a53e-a183f21abbab',
            '@type' => 'Event',
            '@context' => '/contexts/event',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Titel voorbeeld',
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.be/place/dbe91250-4e4b-495c-b692-3da9563b0d52',
            ],
            'calendarType' => 'multiple',
            'startDate' => '2018-01-01T13:00:00+01:00',
            'endDate' => '2018-01-10T17:00:00+01:00',
            'status' => [
                'type' => 'TemporarilyUnavailable',
                'reason' => [
                    'nl' => 'Nederlands',
                    'fr' => 'Frans',
                ],
            ],
            'subEvent' => [
                [
                    '@type' => 'Event',
                    'startDate' => '2018-01-01T13:00:00+01:00',
                    'endDate' => '2018-01-01T17:00:00+01:00',
                    'status' => [
                        'type' => 'Unavailable',
                        'reason' => [
                            'nl' => 'Nederlandse reden',
                            'fr' => 'Franse reden',
                        ],
                    ],
                ],
                [
                    '@type' => 'Event',
                    'startDate' => '2018-01-03T13:00:00+01:00',
                    'endDate' => '2018-01-03T17:00:00+01:00',
                ],
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.1',
                ],
            ],
        ];

        $expected = new ImmutableEvent(
            new Uuid('9f34efc7-a528-4ea8-a53e-a183f21abbab'),
            new Language('nl'),
            new TranslatedTitle(
                new Language('nl'),
                new Title('Titel voorbeeld')
            ),
            (new MultipleSubEventsCalendar(
                new SubEvents(
                    new SubEvent(
                        new DateRange(
                            DateTimeFactory::fromAtom('2018-01-01T13:00:00+01:00'),
                            DateTimeFactory::fromAtom('2018-01-01T17:00:00+01:00')
                        ),
                        new Status(
                            StatusType::Unavailable(),
                            (new TranslatedStatusReason(
                                new Language('nl'),
                                new StatusReason('Nederlandse reden')
                            ))
                                ->withTranslation(new Language('fr'), new StatusReason('Franse reden'))
                        ),
                        new BookingAvailability(BookingAvailabilityType::Available())
                    ),
                    new SubEvent(
                        new DateRange(
                            DateTimeFactory::fromAtom('2018-01-03T13:00:00+01:00'),
                            DateTimeFactory::fromAtom('2018-01-03T17:00:00+01:00')
                        ),
                        new Status(
                            StatusType::TemporarilyUnavailable(),
                            (new TranslatedStatusReason(
                                new Language('nl'),
                                new StatusReason('Nederlands')
                            ))->withTranslation(new Language('fr'), new StatusReason('Frans'))
                        ),
                        new BookingAvailability(BookingAvailabilityType::Available())
                    )
                )
            ))->withStatus(
                new Status(
                    StatusType::TemporarilyUnavailable(),
                    (new TranslatedStatusReason(
                        new Language('nl'),
                        new StatusReason('Nederlands')
                    ))->withTranslation(new Language('fr'), new StatusReason('Frans'))
                )
            ),
            PlaceReference::createWithPlaceId(new Uuid('dbe91250-4e4b-495c-b692-3da9563b0d52')),
            new Categories(
                new Category(
                    new CategoryID('0.50.1.0.1')
                )
            )
        );

        $actual = $this->denormalizer->denormalize($eventData, ImmutableEvent::class);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_denormalize_event_data_with_a_periodic_calendar_and_no_opening_hours(): void
    {
        $eventData = [
            '@id' => 'https://io.uitdatabank.be/event/9f34efc7-a528-4ea8-a53e-a183f21abbab',
            '@type' => 'Event',
            '@context' => '/contexts/event',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Titel voorbeeld',
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.be/place/dbe91250-4e4b-495c-b692-3da9563b0d52',
            ],
            'calendarType' => 'periodic',
            'startDate' => '2018-01-01T13:00:00+01:00',
            'endDate' => '2018-01-10T17:00:00+01:00',
            'terms' => [
                [
                    'id' => '0.50.1.0.1',
                ],
            ],
        ];

        $expected = new ImmutableEvent(
            new Uuid('9f34efc7-a528-4ea8-a53e-a183f21abbab'),
            new Language('nl'),
            new TranslatedTitle(
                new Language('nl'),
                new Title('Titel voorbeeld')
            ),
            new PeriodicCalendar(
                new DateRange(
                    DateTimeFactory::fromAtom('2018-01-01T13:00:00+01:00'),
                    DateTimeFactory::fromAtom('2018-01-10T17:00:00+01:00')
                ),
                new OpeningHours()
            ),
            PlaceReference::createWithPlaceId(new Uuid('dbe91250-4e4b-495c-b692-3da9563b0d52')),
            new Categories(
                new Category(
                    new CategoryID('0.50.1.0.1')
                )
            )
        );

        $actual = $this->denormalizer->denormalize($eventData, ImmutableEvent::class);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_denormalize_event_data_with_a_periodic_calendar_and_opening_hours(): void
    {
        $eventData = [
            '@id' => 'https://io.uitdatabank.be/event/9f34efc7-a528-4ea8-a53e-a183f21abbab',
            '@type' => 'Event',
            '@context' => '/contexts/event',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Titel voorbeeld',
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.be/place/dbe91250-4e4b-495c-b692-3da9563b0d52',
            ],
            'calendarType' => 'periodic',
            'startDate' => '2018-01-01T13:00:00+01:00',
            'endDate' => '2018-01-10T17:00:00+01:00',
            'openingHours' => [
                [
                    'dayOfWeek' => ['monday', 'tuesday'],
                    'opens' => '08:00',
                    'closes' => '12:00',
                ],
                [
                    'dayOfWeek' => ['monday', 'tuesday'],
                    'opens' => '13:00',
                    'closes' => '17:00',
                ],
                [
                    'dayOfWeek' => ['saturday'],
                    'opens' => '08:00',
                    'closes' => '17:45',
                ],
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.1',
                ],
            ],
        ];

        $expected = new ImmutableEvent(
            new Uuid('9f34efc7-a528-4ea8-a53e-a183f21abbab'),
            new Language('nl'),
            new TranslatedTitle(
                new Language('nl'),
                new Title('Titel voorbeeld')
            ),
            new PeriodicCalendar(
                new DateRange(
                    DateTimeFactory::fromAtom('2018-01-01T13:00:00+01:00'),
                    DateTimeFactory::fromAtom('2018-01-10T17:00:00+01:00')
                ),
                new OpeningHours(
                    new OpeningHour(
                        new Days(new Day('monday'), new Day('tuesday')),
                        new Time(new Hour(8), new Minute(0)),
                        new Time(new Hour(12), new Minute(0))
                    ),
                    new OpeningHour(
                        new Days(new Day('monday'), new Day('tuesday')),
                        new Time(new Hour(13), new Minute(0)),
                        new Time(new Hour(17), new Minute(0))
                    ),
                    new OpeningHour(
                        new Days(new Day('saturday')),
                        new Time(new Hour(8), new Minute(0)),
                        new Time(new Hour(17), new Minute(45))
                    )
                )
            ),
            PlaceReference::createWithPlaceId(new Uuid('dbe91250-4e4b-495c-b692-3da9563b0d52')),
            new Categories(
                new Category(
                    new CategoryID('0.50.1.0.1')
                )
            )
        );

        $actual = $this->denormalizer->denormalize($eventData, ImmutableEvent::class);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_denormalize_event_data_with_a_permanent_calendar_and_opening_hours(): void
    {
        $eventData = [
            '@id' => 'https://io.uitdatabank.be/event/9f34efc7-a528-4ea8-a53e-a183f21abbab',
            '@type' => 'Event',
            '@context' => '/contexts/event',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Titel voorbeeld',
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.be/place/dbe91250-4e4b-495c-b692-3da9563b0d52',
            ],
            'calendarType' => 'permanent',
            'openingHours' => [
                [
                    'dayOfWeek' => ['monday', 'tuesday'],
                    'opens' => '08:00',
                    'closes' => '12:00',
                ],
                [
                    'dayOfWeek' => ['monday', 'tuesday'],
                    'opens' => '13:00',
                    'closes' => '17:00',
                ],
                [
                    'dayOfWeek' => ['saturday'],
                    'opens' => '08:00',
                    'closes' => '17:45',
                ],
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.1',
                ],
            ],
        ];

        $expected = new ImmutableEvent(
            new Uuid('9f34efc7-a528-4ea8-a53e-a183f21abbab'),
            new Language('nl'),
            new TranslatedTitle(
                new Language('nl'),
                new Title('Titel voorbeeld')
            ),
            new PermanentCalendar(
                new OpeningHours(
                    new OpeningHour(
                        new Days(new Day('monday'), new Day('tuesday')),
                        new Time(new Hour(8), new Minute(0)),
                        new Time(new Hour(12), new Minute(0))
                    ),
                    new OpeningHour(
                        new Days(new Day('monday'), new Day('tuesday')),
                        new Time(new Hour(13), new Minute(0)),
                        new Time(new Hour(17), new Minute(0))
                    ),
                    new OpeningHour(
                        new Days(new Day('saturday')),
                        new Time(new Hour(8), new Minute(0)),
                        new Time(new Hour(17), new Minute(45))
                    )
                )
            ),
            PlaceReference::createWithPlaceId(new Uuid('dbe91250-4e4b-495c-b692-3da9563b0d52')),
            new Categories(
                new Category(
                    new CategoryID('0.50.1.0.1')
                )
            )
        );

        $actual = $this->denormalizer->denormalize($eventData, ImmutableEvent::class);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_denormalize_event_data_with_terms_with_labels_and_domains(): void
    {
        $eventData = [
            '@id' => 'https://io.uitdatabank.be/event/9f34efc7-a528-4ea8-a53e-a183f21abbab',
            '@type' => 'Event',
            '@context' => '/contexts/event',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Titel voorbeeld',
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.be/place/dbe91250-4e4b-495c-b692-3da9563b0d52',
            ],
            'calendarType' => 'permanent',
            'terms' => [
                [
                    'id' => '0.50.1.0.1',
                    'label' => 'Concert',
                    'domain' => 'eventtype',
                ],
                [
                    'id' => '0.50.2.0.1',
                    'label' => 'blues',
                    'domain' => 'theme',
                ],
            ],
        ];

        $expected = new ImmutableEvent(
            new Uuid('9f34efc7-a528-4ea8-a53e-a183f21abbab'),
            new Language('nl'),
            new TranslatedTitle(
                new Language('nl'),
                new Title('Titel voorbeeld')
            ),
            new PermanentCalendar(new OpeningHours()),
            PlaceReference::createWithPlaceId(new Uuid('dbe91250-4e4b-495c-b692-3da9563b0d52')),
            new Categories(
                new Category(
                    new CategoryID('0.50.1.0.1'),
                    new CategoryLabel('Concert'),
                    new CategoryDomain('eventtype')
                ),
                new Category(
                    new CategoryID('0.50.2.0.1'),
                    new CategoryLabel('blues'),
                    new CategoryDomain('theme')
                )
            )
        );

        $actual = $this->denormalizer->denormalize($eventData, ImmutableEvent::class);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_denormalize_event_data_with_typical_age_range_starting_without_number(): void
    {
        $eventData = [
            '@id' => 'https://io.uitdatabank.be/event/9f34efc7-a528-4ea8-a53e-a183f21abbab',
            '@type' => 'Event',
            '@context' => '/contexts/event',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Titel voorbeeld',
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.be/place/dbe91250-4e4b-495c-b692-3da9563b0d52',
            ],
            'calendarType' => 'permanent',
            'terms' => [
                [
                    'id' => '0.50.1.0.1',
                ],
            ],
            'typicalAgeRange' => '-12',
        ];

        $expected = (new ImmutableEvent(
            new Uuid('9f34efc7-a528-4ea8-a53e-a183f21abbab'),
            new Language('nl'),
            new TranslatedTitle(
                new Language('nl'),
                new Title('Titel voorbeeld')
            ),
            new PermanentCalendar(new OpeningHours()),
            PlaceReference::createWithPlaceId(new Uuid('dbe91250-4e4b-495c-b692-3da9563b0d52')),
            new Categories(
                new Category(
                    new CategoryID('0.50.1.0.1')
                )
            )
        ))->withAgeRange(new AgeRange(new Age(0), new Age(12)));

        $actual = $this->denormalizer->denormalize($eventData, ImmutableEvent::class);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_denormalize_event_data_with_optional_properties(): void
    {
        $eventData = [
            '@id' => 'https://io.uitdatabank.be/event/9f34efc7-a528-4ea8-a53e-a183f21abbab',
            '@type' => 'Event',
            '@context' => '/contexts/event',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Titel voorbeeld',
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.be/place/dbe91250-4e4b-495c-b692-3da9563b0d52',
            ],
            'calendarType' => 'permanent',
            'attendanceMode' => 'online',
            'onlineUrl' => 'https://www.publiq.be/livestream',
            'terms' => [
                [
                    'id' => '0.50.1.0.1',
                ],
            ],
            'description' => [
                'nl' => 'Voorbeeld beschrijving',
                'en' => 'Example description',
            ],
            'labels' => [
                'foo',
                'bar',
            ],
            'hiddenLabels' => [
                'lorem',
                'ipsum',
            ],
            'organizer' => [
                '@id' => 'https://io.uitdatabank.be/organizers/236f736e-5308-4c3a-94f3-da0bd768da7d',
            ],
            'typicalAgeRange' => '8-12',
            'audience' => [
                'audienceType' => 'education',
            ],
            'priceInfo' => [
                [
                    'category' => 'tariff',
                    'name' => [
                        'nl' => 'Senioren',
                        'en' => 'Seniors',
                    ],
                    'price' => 10.5,
                    'priceCurrency' => 'EUR',
                ],
                [
                    'category' => 'base',
                    'name' => [
                        'nl' => 'Basistarief',
                        'en' => 'Base tariff',
                    ],
                    'price' => 15,
                    'priceCurrency' => 'EUR',
                ],
            ],
            'bookingInfo' => [
                'phone' => '02 551 18 70',
                'email' => 'info@publiq.be',
                'url' => 'https://www.publiq.be',
                'urlLabel' => [
                    'nl' => 'Publiq',
                    'fr' => 'Publiq FR',
                ],
                'availabilityStarts' => '2018-01-01T00:00:00+01:00',
                'availabilityEnds' => '2018-10-01T00:00:00+01:00',
            ],
            'contactPoint' => [
                'phone' => [
                    '044/556677',
                    '011/223344',
                ],
                'email' => [
                    'foo@publiq.be',
                    'bar@publiq.be',
                ],
                'url' => [
                    'https://www.uitdatabank.be',
                    'https://www.uitpas.be',
                ],
            ],
            'mediaObject' => [
                [
                    '@id' => 'https://io.uitdatabank.be/media/8b3c82d5-6cfe-442e-946c-1f4452636d61',
                    'description' => 'Example image 1',
                    'copyrightHolder' => 'Alice',
                    'inLanguage' => 'nl',
                ],
                [
                    '@id' => 'https://io.uitdatabank.be/media/fc712fef-e7c9-4df6-8655-da943852bd8d',
                    '@type' => 'schema:ImageObject',
                    'description' => 'Example image 2',
                    'copyrightHolder' => 'Bob',
                    'inLanguage' => 'fr',
                    'contentUrl' => 'https://io.uitdatabank.be/media/fc712fef-e7c9-4df6-8655-da943852bd8d.png',
                    'thumbnailUrl' => 'https://io.uitdatabank.be/media/fc712fef-e7c9-4df6-8655-da943852bd8d.png',
                ],
            ],
            'videos' => [
                [
                    'id' => '5c549a24-bb97-4f83-8ea5-21a6d56aff72',
                    'url' => 'https://vimeo.com/98765432',
                    'language' => 'nl',
                    'copyrightHolder' => 'publiq',
                ],
                [
                    'id' => '91c75325-3830-4000-b580-5778b2de4548',
                    'url' => 'https://www.youtube.com/watch?v=cEItmb_a20D',
                    'language' => 'fr',
                ],
                [
                    'url' => 'https://www.youtube.com/watch?v=cEItmb_234',
                    'language' => 'nl',
                ],
            ],
            'workflowStatus' => 'APPROVED',
            'availableFrom' => '2018-01-01T00:00:00+01:00',
        ];

        $expected = new ImmutableEvent(
            new Uuid('9f34efc7-a528-4ea8-a53e-a183f21abbab'),
            new Language('nl'),
            new TranslatedTitle(
                new Language('nl'),
                new Title('Titel voorbeeld')
            ),
            new PermanentCalendar(new OpeningHours()),
            PlaceReference::createWithPlaceId(new Uuid('dbe91250-4e4b-495c-b692-3da9563b0d52')),
            new Categories(
                new Category(
                    new CategoryID('0.50.1.0.1')
                )
            )
        );

        $expected = $expected
            ->withDescription(
                (new TranslatedDescription(new Language('nl'), new Description('Voorbeeld beschrijving')))
                    ->withTranslation(new Language('en'), new Description('Example description'))
            )
            ->withAvailableFrom(
                DateTimeFactory::fromAtom('2018-01-01T00:00:00+01:00')
            )
            ->withLabels(
                new Labels(
                    new Label(new LabelName('foo'), true),
                    new Label(new LabelName('bar'), true),
                    new Label(new LabelName('lorem'), false),
                    new Label(new LabelName('ipsum'), false)
                )
            )
            ->withOrganizerReference(
                OrganizerReference::createWithOrganizerId(new Uuid('236f736e-5308-4c3a-94f3-da0bd768da7d'))
            )
            ->withAgeRange(
                new AgeRange(new Age(8), new Age(12))
            )
            ->withAttendanceMode(
                AttendanceMode::online()
            )
            ->withOnlineUrl(
                new Url('https://www.publiq.be/livestream')
            )
            ->withAudienceType(
                AudienceType::education()
            )
            ->withPriceInfo(
                new PriceInfo(
                    new Tariff(
                        (new TranslatedTariffName(
                            new Language('nl'),
                            new TariffName('Basistarief')
                        ))->withTranslation(new Language('en'), new TariffName('Base tariff')),
                        new Money(
                            1500,
                            new Currency('EUR')
                        )
                    ),
                    new Tariffs(
                        new Tariff(
                            (new TranslatedTariffName(
                                new Language('nl'),
                                new TariffName('Senioren')
                            ))->withTranslation(new Language('en'), new TariffName('Seniors')),
                            new Money(
                                1050,
                                new Currency('EUR')
                            )
                        )
                    )
                )
            )
            ->withBookingInfo(
                new BookingInfo(
                    new WebsiteLink(
                        new Url('https://www.publiq.be'),
                        (new TranslatedWebsiteLabel(
                            new Language('nl'),
                            new WebsiteLabel('Publiq')
                        ))->withTranslation(new Language('fr'), new WebsiteLabel('Publiq FR'))
                    ),
                    new TelephoneNumber('02 551 18 70'),
                    new EmailAddress('info@publiq.be'),
                    new ContactBookingAvailability(
                        DateTimeFactory::fromAtom('2018-01-01T00:00:00+01:00'),
                        DateTimeFactory::fromAtom('2018-10-01T00:00:00+01:00')
                    )
                )
            )
            ->withContactPoint(
                new ContactPoint(
                    new TelephoneNumbers(
                        new TelephoneNumber('044/556677'),
                        new TelephoneNumber('011/223344')
                    ),
                    new EmailAddresses(
                        new EmailAddress('foo@publiq.be'),
                        new EmailAddress('bar@publiq.be')
                    ),
                    new Urls(
                        new Url('https://www.uitdatabank.be'),
                        new Url('https://www.uitpas.be')
                    )
                )
            )
            ->withMediaObjectReferences(
                new MediaObjectReferences(
                    MediaObjectReference::createWithMediaObjectId(
                        new Uuid('8b3c82d5-6cfe-442e-946c-1f4452636d61'),
                        new Description('Example image 1'),
                        new CopyrightHolder('Alice'),
                        new Language('nl')
                    ),
                    MediaObjectReference::createWithEmbeddedMediaObject(
                        new MediaObject(
                            new Uuid('fc712fef-e7c9-4df6-8655-da943852bd8d'),
                            MediaObjectType::imageObject(),
                            new Url('https://io.uitdatabank.be/media/fc712fef-e7c9-4df6-8655-da943852bd8d.png'),
                            new Url('https://io.uitdatabank.be/media/fc712fef-e7c9-4df6-8655-da943852bd8d.png')
                        ),
                        new Description('Example image 2'),
                        new CopyrightHolder('Bob'),
                        new Language('fr')
                    )
                )
            )
            ->withVideos(
                new VideoCollection(
                    (new Video(
                        '5c549a24-bb97-4f83-8ea5-21a6d56aff72',
                        new Url('https://vimeo.com/98765432'),
                        new Language('nl'),
                    ))->withCopyrightHolder(new CopyrightHolder('publiq')),
                    new Video(
                        '91c75325-3830-4000-b580-5778b2de4548',
                        new Url('https://www.youtube.com/watch?v=cEItmb_a20D'),
                        new Language('fr')
                    ),
                    new Video(
                        '7bddfbb5-7c79-48af-a154-4a44df395608',
                        new Url('https://www.youtube.com/watch?v=cEItmb_234'),
                        new Language('nl')
                    )
                )
            )
            ->withWorkflowStatus(
                WorkflowStatus::APPROVED()
            );

        $this->uuidFactory->expects($this->once())
            ->method('uuid4')
            ->willReturn(\Ramsey\Uuid\Uuid::fromString('7bddfbb5-7c79-48af-a154-4a44df395608'));

        $actual = $this->denormalizer->denormalize($eventData, ImmutableEvent::class);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_throws_on_invalid_organizer_reference(): void
    {
        $eventData = [
            '@id' => 'https://io.uitdatabank.be/event/9f34efc7-a528-4ea8-a53e-a183f21abbab',
            '@type' => 'Event',
            '@context' => '/contexts/event',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Titel voorbeeld',
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.be/place/dbe91250-4e4b-495c-b692-3da9563b0d52',
            ],
            'organizer' => [
                '@id' => 'Invalid id',
            ],
            'calendarType' => 'permanent',
            'terms' => [
                [
                    'id' => '0.50.1.0.1',
                ],
            ],
        ];

        $this->expectException(InvalidUrl::class);

        $this->denormalizer->denormalize($eventData, ImmutableEvent::class);
    }

    /**
     * @test
     */
    public function it_throws_on_missing_organizer_reference_id(): void
    {
        $eventData = [
            '@id' => 'https://io.uitdatabank.be/event/9f34efc7-a528-4ea8-a53e-a183f21abbab',
            '@type' => 'Event',
            '@context' => '/contexts/event',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Titel voorbeeld',
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.be/place/dbe91250-4e4b-495c-b692-3da9563b0d52',
            ],
            'organizer' => [
                'id' => 'https://io.uitdatabank.be/organizers/236f736e-5308-4c3a-94f3-da0bd768da7d',
            ],
            'calendarType' => 'permanent',
            'terms' => [
                [
                    'id' => '0.50.1.0.1',
                ],
            ],
        ];

        $this->expectException(\InvalidArgumentException::class);

        $this->denormalizer->denormalize($eventData, ImmutableEvent::class);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_when_trying_to_denormalize_to_an_unsupported_class(): void
    {
        $this->expectException(UnsupportedException::class);
        $this->denormalizer->denormalize([], ImmutablePlace::class);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_when_trying_to_denormalize_data_that_is_not_an_array(): void
    {
        $this->expectException(UnsupportedException::class);
        $this->denormalizer->denormalize(new \stdClass(), ImmutableEvent::class);
    }

    /**
     * @test
     */
    public function it_should_support_denormalization_to_immutable_event(): void
    {
        $this->assertTrue(
            $this->denormalizer->supportsDenormalization([], ImmutableEvent::class)
        );
    }
}
