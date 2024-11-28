<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use Broadway\Domain\DomainMessage;
use Broadway\EventSourcing\Testing\AggregateRootScenarioTestCase;
use CultuurNet\UDB3\BookingInfo;
use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Event\Events\AttendanceModeUpdated;
use CultuurNet\UDB3\Event\Events\AudienceUpdated;
use CultuurNet\UDB3\Event\Events\BookingInfoUpdated;
use CultuurNet\UDB3\Event\Events\CalendarUpdated;
use CultuurNet\UDB3\Event\Events\ContactPointUpdated;
use CultuurNet\UDB3\Event\Events\EventCopied;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\EventImportedFromUDB2;
use CultuurNet\UDB3\Event\Events\EventUpdatedFromUDB2;
use CultuurNet\UDB3\Event\Events\FacilitiesUpdated;
use CultuurNet\UDB3\Event\Events\ImageAdded;
use CultuurNet\UDB3\Event\Events\ImageRemoved;
use CultuurNet\UDB3\Event\Events\LabelAdded;
use CultuurNet\UDB3\Event\Events\LabelRemoved;
use CultuurNet\UDB3\Event\Events\LabelsImported;
use CultuurNet\UDB3\Event\Events\LocationUpdated;
use CultuurNet\UDB3\Event\Events\MajorInfoUpdated;
use CultuurNet\UDB3\Event\Events\Moderation\Published;
use CultuurNet\UDB3\Event\Events\OnlineUrlDeleted;
use CultuurNet\UDB3\Event\Events\OnlineUrlUpdated;
use CultuurNet\UDB3\Event\Events\PriceInfoUpdated;
use CultuurNet\UDB3\Event\Events\TypicalAgeRangeDeleted;
use CultuurNet\UDB3\Event\Events\TypicalAgeRangeUpdated;
use CultuurNet\UDB3\Facility;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Media\Properties\Description;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use CultuurNet\UDB3\Model\ValueObject\Audience\Age;
use CultuurNet\UDB3\Model\ValueObject\Audience\AgeRange;
use CultuurNet\UDB3\Model\ValueObject\Audience\AudienceType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\CalendarType;
use CultuurNet\UDB3\Model\ValueObject\Contact\ContactPoint;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumber;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumbers;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\Price\PriceInfo;
use CultuurNet\UDB3\Model\ValueObject\Price\Tariff;
use CultuurNet\UDB3\Model\ValueObject\Price\TariffName;
use CultuurNet\UDB3\Model\ValueObject\Price\Tariffs;
use CultuurNet\UDB3\Model\ValueObject\Price\TranslatedTariffName;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Online\AttendanceMode;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddresses;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Model\ValueObject\Web\Urls;
use CultuurNet\UDB3\SampleFiles;
use CultuurNet\UDB3\Theme;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use CultuurNet\UDB3\ValueObject\MultilingualString;
use Money\Currency;
use Money\Money;
use RuntimeException;

class EventTest extends AggregateRootScenarioTestCase
{
    public const NS_CDBXML_3_2 = 'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL';
    public const NS_CDBXML_3_3 = 'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL';

    protected function getAggregateRootClass(): string
    {
        return Event::class;
    }

    protected Event $event;

    public function setUp(): void
    {
        parent::setUp();

        $this->event = Event::create(
            'foo',
            new Language('en'),
            new Title('some representative title'),
            new EventType('0.50.4.0.0', 'concert'),
            new LocationId('d70f5d94-7072-423d-9144-9354cb794c62'),
            new Calendar(CalendarType::permanent())
        );
    }

    private function getCreationEvent(): EventCreated
    {
        return new EventCreated(
            'd2b41f1d-598c-46af-a3a5-10e373faa6fe',
            new Language('en'),
            'some representative title',
            new EventType('0.50.4.0.0', 'concert'),
            new LocationId('322d67b6-e84d-4649-9384-12ecad74eab3'),
            new Calendar(CalendarType::permanent())
        );
    }

    private function getCreationEventWithTheme(): EventCreated
    {
        return new EventCreated(
            'd2b41f1d-598c-46af-a3a5-10e373faa6fe',
            new Language('en'),
            'some representative title',
            new EventType('0.50.4.0.0', 'concert'),
            new LocationId('59400d1e-6f98-4da9-ab08-f58adceb7204'),
            new Calendar(CalendarType::permanent()),
            new Theme('1.8.3.1.0', 'Pop en rock')
        );
    }

    /**
     * @test
     */
    public function it_handles_update_attendanceMode(): void
    {
        $this->scenario
            ->given([
                $this->getCreationEvent(),
            ])
            ->when(
                fn (Event $event) => $event->updateAttendanceMode(AttendanceMode::online())
            )
            ->then([
                new AttendanceModeUpdated('d2b41f1d-598c-46af-a3a5-10e373faa6fe', AttendanceMode::online()->toString()),
            ]);
    }

    /**
     * @test
     */
    public function it_removes_onlineUrl_for_offline_attendanceMode(): void
    {
        $this->scenario
            ->given([
                $this->getCreationEvent(),
                new AttendanceModeUpdated('d2b41f1d-598c-46af-a3a5-10e373faa6fe', AttendanceMode::online()->toString()),
                new OnlineUrlUpdated('d2b41f1d-598c-46af-a3a5-10e373faa6fe', 'https://www.publiq.be/livestream'),
            ])
            ->when(
                fn (Event $event) => $event->updateAttendanceMode(AttendanceMode::offline())
            )
            ->then([
                new AttendanceModeUpdated('d2b41f1d-598c-46af-a3a5-10e373faa6fe', AttendanceMode::offline()->toString()),
                new OnlineUrlDeleted('d2b41f1d-598c-46af-a3a5-10e373faa6fe'),
            ]);
    }

    /**
     * @test
     */
    public function it_handles_update_onlineUrl_on_online_event(): void
    {
        $this->scenario
            ->given([
                $this->getCreationEvent(),
                new AttendanceModeUpdated('d2b41f1d-598c-46af-a3a5-10e373faa6fe', AttendanceMode::online()->toString()),
            ])
            ->when(
                fn (Event $event) => $event->updateOnlineUrl(new Url('https://www.publiq.be/livestream'))
            )
            ->then([
                new OnlineUrlUpdated('d2b41f1d-598c-46af-a3a5-10e373faa6fe', 'https://www.publiq.be/livestream'),
            ]);
    }

    /**
     * @test
     */
    public function it_handles_update_onlineUrl_on_mixed_event(): void
    {
        $this->scenario
            ->given([
                $this->getCreationEvent(),
                new AttendanceModeUpdated('d2b41f1d-598c-46af-a3a5-10e373faa6fe', AttendanceMode::mixed()->toString()),
            ])
            ->when(
                fn (Event $event) => $event->updateOnlineUrl(new Url('https://www.publiq.be/livestream'))
            )
            ->then([
                new OnlineUrlUpdated('d2b41f1d-598c-46af-a3a5-10e373faa6fe', 'https://www.publiq.be/livestream'),
            ]);
    }

    /**
     * @test
     */
    public function it_throws_when_updating_onlineUrl_on_offline_event(): void
    {
        $this->expectException(UpdateOnlineUrlNotSupported::class);
        $this->expectExceptionMessage('');

        $this->scenario
            ->given([
                $this->getCreationEvent(),
            ])
            ->when(
                fn (Event $event) => $event->updateOnlineUrl(new Url('https://www.publiq.be/livestream'))
            )
            ->then([
                new OnlineUrlUpdated('d2b41f1d-598c-46af-a3a5-10e373faa6fe', 'https://www.publiq.be/livestream'),
            ]);
    }

    /**
     * @test
     */
    public function it_ignores_same_onlineUrl(): void
    {
        $this->scenario
            ->given([
                $this->getCreationEvent(),
                new AttendanceModeUpdated('d2b41f1d-598c-46af-a3a5-10e373faa6fe', AttendanceMode::mixed()->toString()),
                new OnlineUrlUpdated('d2b41f1d-598c-46af-a3a5-10e373faa6fe', 'https://www.publiq.be/livestream'),
            ])
            ->when(
                fn (Event $event) => $event->updateOnlineUrl(new Url('https://www.publiq.be/livestream'))
            )
            ->then([]);
    }

    /**
     * @test
     */
    public function it_deletes_an_onlineUrl(): void
    {
        $this->scenario
            ->given([
                $this->getCreationEvent(),
                new AttendanceModeUpdated('d2b41f1d-598c-46af-a3a5-10e373faa6fe', AttendanceMode::mixed()->toString()),
                new OnlineUrlUpdated('d2b41f1d-598c-46af-a3a5-10e373faa6fe', 'https://www.publiq.be/livestream'),
            ])
            ->when(
                fn (Event $event) => $event->deleteOnlineUrl()
            )
            ->then([
                new OnlineUrlDeleted('d2b41f1d-598c-46af-a3a5-10e373faa6fe'),
            ]);
    }

    /**
     * @test
     */
    public function it_ignores_deleting_an_empty_onlineUrl(): void
    {
        $this->scenario
            ->given([
                $this->getCreationEvent(),
                new AttendanceModeUpdated('d2b41f1d-598c-46af-a3a5-10e373faa6fe', AttendanceMode::mixed()->toString()),
            ])
            ->when(
                fn (Event $event) => $event->deleteOnlineUrl()
            )
            ->then([]);
    }

    /**
     * @test
     */
    public function it_updates_attendance_mode_on_major_info_updated_on_offline_event(): void
    {
        $this->scenario
            ->given([
                $this->getCreationEvent(),
            ])
            ->when(
                function (Event $event) {
                    $event->updateMajorInfo(
                        new Title('foo'),
                        new EventType('0.50.4.0.0', 'concert'),
                        new LocationId('00000000-0000-0000-0000-000000000000'),
                        new Calendar(CalendarType::permanent())
                    );
                    $event->updateAttendanceMode(AttendanceMode::online());
                }
            )
            ->then([
                new MajorInfoUpdated(
                    'd2b41f1d-598c-46af-a3a5-10e373faa6fe',
                    'foo',
                    new EventType('0.50.4.0.0', 'concert'),
                    new LocationId('00000000-0000-0000-0000-000000000000'),
                    new Calendar(CalendarType::permanent())
                ),
            ]);
    }

    /**
     * @test
     */
    public function it_updates_attendance_mode_on_major_info_updated_on_event_with_online_url(): void
    {
        $this->scenario
            ->given([
                $this->getCreationEvent(),
                new OnlineUrlUpdated('d2b41f1d-598c-46af-a3a5-10e373faa6fe', 'https://www.publiq.be/livestream'),
            ])
            ->when(
                function (Event $event) {
                    $event->updateMajorInfo(
                        new Title('foo'),
                        new EventType('0.50.4.0.0', 'concert'),
                        new LocationId('d0cd4e9d-3cf1-4324-9835-2bfba63ac015'),
                        new Calendar(CalendarType::permanent())
                    );
                    $event->updateAttendanceMode(AttendanceMode::mixed());
                }
            )
            ->then([
                new MajorInfoUpdated(
                    'd2b41f1d-598c-46af-a3a5-10e373faa6fe',
                    'foo',
                    new EventType('0.50.4.0.0', 'concert'),
                    new LocationId('d0cd4e9d-3cf1-4324-9835-2bfba63ac015'),
                    new Calendar(CalendarType::permanent())
                ),
            ]);
    }

    /**
     * @test
     */
    public function it_updates_attendance_mode_on_major_info_updated_on_online_event(): void
    {
        $this->scenario
            ->given([
                $this->getCreationEvent(),
                new AttendanceModeUpdated('d2b41f1d-598c-46af-a3a5-10e373faa6fe', AttendanceMode::online()->toString()),
            ])
            ->when(
                function (Event $event) {
                    $event->updateMajorInfo(
                        new Title('foo'),
                        new EventType('0.50.4.0.0', 'concert'),
                        new LocationId('d0cd4e9d-3cf1-4324-9835-2bfba63ac015'),
                        new Calendar(CalendarType::permanent())
                    );
                    $event->updateAttendanceMode(AttendanceMode::offline());
                }
            )
            ->then([
                new MajorInfoUpdated(
                    'd2b41f1d-598c-46af-a3a5-10e373faa6fe',
                    'foo',
                    new EventType('0.50.4.0.0', 'concert'),
                    new LocationId('d0cd4e9d-3cf1-4324-9835-2bfba63ac015'),
                    new Calendar(CalendarType::permanent())
                ),
            ]);
    }

    /**
     * @test
     */
    public function it_sets_the_audience_type_to_education_when_creating_an_event_with_a_dummy_education_location(): void
    {
        $eventUuid = '441372bd-082b-431b-9b99-d53bee093ec8';
        $locationUuid = '35855fa6-ab1c-46ab-a0ff-ab910c8300e1';
        LocationId::setDummyPlaceForEducationIds([$locationUuid]);

        $event = Event::create(
            $eventUuid,
            new Language('en'),
            new Title('some representative title'),
            new EventType('0.50.4.0.0', 'concert'),
            new LocationId($locationUuid),
            new Calendar(CalendarType::permanent())
        );

        $expectedEvent = new AudienceUpdated($eventUuid, AudienceType::education());

        $actualEvents = array_map(
            function (DomainMessage $domainMessage) {
                return $domainMessage->getPayload();
            },
            iterator_to_array($event->getUncommittedEvents()->getIterator())
        );

        $this->assertEquals($expectedEvent, $actualEvents[1]);
    }

    /**
     * @test
     * @group issue-III-1380
     */
    public function it_handles_copy_event(): void
    {
        $newEventId = 'e49430ca-5729-4768-8364-02ddb385517a';
        $calendar = new Calendar(
            CalendarType::permanent()
        );

        $event = $this->event;

        $this->event->getUncommittedEvents();

        $this->scenario
            ->when(function () use ($event, $newEventId, $calendar) {
                return $event->copy(
                    $newEventId,
                    $calendar
                );
            })
            ->then(
                [
                    new EventCopied(
                        $newEventId,
                        'foo',
                        $calendar
                    ),
                ]
            );
    }

    /**
     * @test
     */
    public function it_handles_update_facilities_after_udb2_update(): void
    {
        $eventId = 'd2b41f1d-598c-46af-a3a5-10e373faa6fe';
        $createEvent = $this->getCreationEvent();

        $facilities = [
            new Facility('3.27.0.0.0', 'Rolstoeltoegankelijk'),
            new Facility('3.30.0.0.0', 'Rolstoelpodium'),
        ];

        $xmlData = $this->getSample('EventTest.cdbxml.xml');
        $xmlNamespace = self::NS_CDBXML_3_2;

        $this->scenario
            ->given(
                [
                    $createEvent,
                    new FacilitiesUpdated($eventId, $facilities),
                    new EventUpdatedFromUDB2($eventId, $xmlData, $xmlNamespace),
                ]
            )
            ->when(
                function (Event $event) use ($facilities): void {
                    $event->updateFacilities($facilities);
                }
            )
            ->then(
                [
                    new FacilitiesUpdated($eventId, $facilities),
                ]
            );
    }

    /**
     * @test
     */
    public function it_handles_update_contact_point_after_udb2_import(): void
    {
        $eventId = 'd2b41f1d-598c-46af-a3a5-10e373faa6fe';
        $createEvent = $this->getCreationEvent();

        $contactPoint = new ContactPoint(
            new TelephoneNumbers(new TelephoneNumber('016/101010')),
            new EmailAddresses(new EmailAddress('test@2dotstwice.be'), new EmailAddress('admin@2dotstwice.be')),
            new Urls(new Url('http://www.2dotstwice.be'))
        );

        $xmlData = $this->getSample('EventTest.cdbxml.xml');
        $xmlNamespace = self::NS_CDBXML_3_2;

        $this->scenario
            ->given(
                [
                    $createEvent,
                    new ContactPointUpdated($eventId, $contactPoint),
                    new EventUpdatedFromUDB2($eventId, $xmlData, $xmlNamespace),
                ]
            )
            ->when(
                function (Event $event) use ($contactPoint): void {
                    $event->updateContactPoint($contactPoint);
                }
            )
            ->then(
                [
                    new ContactPointUpdated($eventId, $contactPoint),
                ]
            );
    }

    /**
     * @test
     */
    public function it_handles_update_calendar_after_udb2_import(): void
    {
        $eventId = 'd2b41f1d-598c-46af-a3a5-10e373faa6fe';
        $createEvent = $this->getCreationEvent();

        $calendar = new Calendar(
            CalendarType::periodic(),
            DateTimeFactory::fromAtom('2020-01-26T11:11:11+01:00'),
            DateTimeFactory::fromAtom('2020-01-27T12:12:12+01:00')
        );

        $xmlData = $this->getSample('EventTest.cdbxml.xml');
        $xmlNamespace = self::NS_CDBXML_3_2;

        $this->scenario
            ->given(
                [
                    $createEvent,
                    new CalendarUpdated($eventId, $calendar),
                    new EventUpdatedFromUDB2($eventId, $xmlData, $xmlNamespace),
                ]
            )
            ->when(
                function (Event $event) use ($calendar): void {
                    $event->updateCalendar($calendar);
                }
            )
            ->then(
                [
                    new CalendarUpdated($eventId, $calendar),
                ]
            );
    }

    /**
     * @test
     */
    public function it_handles_update_typical_age_range_after_udb2_update(): void
    {
        $eventId = 'd2b41f1d-598c-46af-a3a5-10e373faa6fe';
        $createEvent = $this->getCreationEvent();

        $typicalAgeRange = new AgeRange(new Age(8), new Age(11));
        $otherTypicalAgeRange = new AgeRange(new Age(7), new Age(11));

        $xmlData = $this->getSample('EventTest_WithAgeRange.cdbxml.xml');
        $xmlNamespace = self::NS_CDBXML_3_2;

        $this->scenario
            ->given(
                [
                    $createEvent,
                    new EventUpdatedFromUDB2($eventId, $xmlData, $xmlNamespace),
                ]
            )
            ->when(
                function (Event $event) use ($typicalAgeRange, $otherTypicalAgeRange): void {
                    $event->updateTypicalAgeRange($typicalAgeRange);
                    $event->updateTypicalAgeRange($otherTypicalAgeRange);
                }
            )
            ->then(
                [
                    new TypicalAgeRangeUpdated($eventId, $otherTypicalAgeRange),
                ]
            );
    }

    /**
     * @test
     */
    public function it_handles_delete_typical_age_range_after_udb2_update(): void
    {
        $eventId = 'd2b41f1d-598c-46af-a3a5-10e373faa6fe';
        $createEvent = $this->getCreationEvent();

        $xmlData = $this->getSample('EventTest_WithAgeRange.cdbxml.xml');
        $xmlNamespace = self::NS_CDBXML_3_2;

        $this->scenario
            ->given(
                [
                    $createEvent,
                    new EventUpdatedFromUDB2($eventId, $xmlData, $xmlNamespace),
                ]
            )
            ->when(
                function (Event $event): void {
                    $event->deleteTypicalAgeRange();
                }
            )
            ->then(
                [
                    new TypicalAgeRangeDeleted($eventId),
                ]
            );
    }

    /**
     * @test
     */
    public function it_handles_update_booking_info_after_udb2_update(): void
    {
        $eventId = 'd2b41f1d-598c-46af-a3a5-10e373faa6fe';
        $createEvent = $this->getCreationEvent();

        $bookingInfo = new BookingInfo(
            'www.publiq.be',
            new MultilingualString(new Language('nl'), 'publiq'),
            '02 123 45 67',
            'info@publiq.be'
        );
        $xmlData = $this->getSample('EventTest.cdbxml.xml');
        $xmlNamespace = self::NS_CDBXML_3_2;

        $this->scenario
            ->given(
                [
                    $createEvent,
                    new BookingInfoUpdated($eventId, $bookingInfo),
                    new EventUpdatedFromUDB2($eventId, $xmlData, $xmlNamespace),
                ]
            )
            ->when(
                function (Event $event) use ($bookingInfo): void {
                    $event->updateBookingInfo($bookingInfo);
                }
            )
            ->then(
                [
                    new BookingInfoUpdated($eventId, $bookingInfo),
                ]
            );
    }

    /**
     * @test
     */
    public function it_keeps_existing_uitpas_prices_on_price_info_update(): void
    {
        $this->scenario
            ->given([
                $this->getCreationEvent(),
                new PriceInfoUpdated(
                    'd2b41f1d-598c-46af-a3a5-10e373faa6fe',
                    new PriceInfo(
                        Tariff::createBasePrice(
                            new Money(
                                100,
                                new Currency('EUR')
                            )
                        ),
                        new Tariffs()
                    )
                ),
                new PriceInfoUpdated(
                    'd2b41f1d-598c-46af-a3a5-10e373faa6fe',
                    (new PriceInfo(
                        Tariff::createBasePrice(new Money(100, new Currency('EUR'))),
                        new Tariffs()
                    ))->withUiTPASTariffs(
                        new Tariffs(
                            new Tariff(
                                new TranslatedTariffName(
                                    new Language('nl'),
                                    new TariffName('Tariff 1')
                                ),
                                new Money(
                                    199,
                                    new Currency('EUR')
                                )
                            ),
                        )
                    )
                ),
            ])
            ->when(
                fn (Event $event) => $event->updatePriceInfo(
                    new PriceInfo(
                        Tariff::createBasePrice(
                            new Money(
                                90,
                                new Currency('EUR')
                            )
                        ),
                        new Tariffs()
                    )
                )
            )
            ->then([
                new PriceInfoUpdated(
                    'd2b41f1d-598c-46af-a3a5-10e373faa6fe',
                    (new PriceInfo(
                        Tariff::createBasePrice(new Money(90, new Currency('EUR'))),
                        new Tariffs()
                    ))->withUiTPASTariffs(
                        new Tariffs(
                            new Tariff(
                                new TranslatedTariffName(
                                    new Language('nl'),
                                    new TariffName('Tariff 1')
                                ),
                                new Money(
                                    199,
                                    new Currency('EUR')
                                )
                            ),
                        )
                    )
                ),
            ]);
    }

    /**
     * @test
     */
    public function it_ignores_an_update_of_uitpas_prices(): void
    {
        $this->scenario
            ->given([
                $this->getCreationEvent(),
                new PriceInfoUpdated(
                    'd2b41f1d-598c-46af-a3a5-10e373faa6fe',
                    new PriceInfo(
                        Tariff::createBasePrice(
                            new Money(
                                100,
                                new Currency('EUR')
                            )
                        ),
                        new Tariffs()
                    )
                ),
                new PriceInfoUpdated(
                    'd2b41f1d-598c-46af-a3a5-10e373faa6fe',
                    (new PriceInfo(
                        Tariff::createBasePrice(new Money(100, new Currency('EUR'))),
                        new Tariffs()
                    ))->withUiTPASTariffs(
                        new Tariffs(
                            new Tariff(
                                new TranslatedTariffName(
                                    new Language('nl'),
                                    new TariffName('Tariff 1')
                                ),
                                new Money(
                                    199,
                                    new Currency('EUR')
                                )
                            ),
                        )
                    )
                ),
            ])
            ->when(
                fn (Event $event) => $event->updatePriceInfo(
                    (new PriceInfo(
                        Tariff::createBasePrice(
                            new Money(
                                90,
                                new Currency('EUR')
                            )
                        ),
                        new Tariffs()
                    ))->withUiTPASTariffs(
                        new Tariffs(
                            new Tariff(
                                new TranslatedTariffName(
                                    new Language('nl'),
                                    new TariffName('Tariff 1')
                                ),
                                new Money(
                                    80,
                                    new Currency('EUR')
                                )
                            )
                        )
                    )
                )
            )
            ->then([
                new PriceInfoUpdated(
                    'd2b41f1d-598c-46af-a3a5-10e373faa6fe',
                    (new PriceInfo(
                        Tariff::createBasePrice(new Money(90, new Currency('EUR'))),
                        new Tariffs()
                    ))->withUiTPASTariffs(
                        new Tariffs(
                            new Tariff(
                                new TranslatedTariffName(
                                    new Language('nl'),
                                    new TariffName('Tariff 1')
                                ),
                                new Money(
                                    199,
                                    new Currency('EUR')
                                )
                            ),
                        )
                    )
                ),
            ]);
    }

    /**
     * @test
     */
    public function it_ignores_an_update_with_equal_prices_without_uitpas(): void
    {
        $this->scenario
            ->given([
                $this->getCreationEvent(),
                new PriceInfoUpdated(
                    'd2b41f1d-598c-46af-a3a5-10e373faa6fe',
                    new PriceInfo(
                        Tariff::createBasePrice(
                            new Money(
                                100,
                                new Currency('EUR')
                            )
                        ),
                        new Tariffs()
                    )
                ),
                new PriceInfoUpdated(
                    'd2b41f1d-598c-46af-a3a5-10e373faa6fe',
                    (new PriceInfo(
                        Tariff::createBasePrice(new Money(100, new Currency('EUR'))),
                        new Tariffs()
                    ))->withUiTPASTariffs(
                        new Tariffs(
                            new Tariff(
                                new TranslatedTariffName(
                                    new Language('nl'),
                                    new TariffName('Tariff 1')
                                ),
                                new Money(
                                    199,
                                    new Currency('EUR')
                                )
                            ),
                        )
                    )
                ),
            ])
            ->when(
                fn (Event $event) => $event->updatePriceInfo(
                    new PriceInfo(
                        Tariff::createBasePrice(
                            new Money(
                                100,
                                new Currency('EUR')
                            )
                        ),
                        new Tariffs()
                    )
                )
            )
            ->then([]);
    }

    /**
     * @test
     */
    public function it_ignores_an_update_with_only_different_uitpas_prices(): void
    {
        $this->scenario
            ->given([
                $this->getCreationEvent(),
                new PriceInfoUpdated(
                    'd2b41f1d-598c-46af-a3a5-10e373faa6fe',
                    new PriceInfo(
                        Tariff::createBasePrice(
                            new Money(
                                100,
                                new Currency('EUR')
                            )
                        ),
                        new Tariffs()
                    )
                ),
                new PriceInfoUpdated(
                    'd2b41f1d-598c-46af-a3a5-10e373faa6fe',
                    (new PriceInfo(
                        Tariff::createBasePrice(new Money(100, new Currency('EUR'))),
                        new Tariffs()
                    ))->withUiTPASTariffs(
                        new Tariffs(
                            new Tariff(
                                new TranslatedTariffName(
                                    new Language('nl'),
                                    new TariffName('Tariff 1')
                                ),
                                new Money(
                                    199,
                                    new Currency('EUR')
                                )
                            ),
                        )
                    )
                ),
            ])
            ->when(
                fn (Event $event) => $event->updatePriceInfo(
                    (new PriceInfo(
                        Tariff::createBasePrice(
                            new Money(
                                100,
                                new Currency('EUR')
                            )
                        ),
                        new Tariffs()
                    ))->withUiTPASTariffs(
                        new Tariffs(
                            new Tariff(
                                new TranslatedTariffName(
                                    new Language('nl'),
                                    new TariffName('Tariff 1')
                                ),
                                new Money(
                                    80,
                                    new Currency('EUR')
                                )
                            )
                        )
                    )
                )
            )
            ->then([]);
    }

    /**
     * @test
     */
    public function it_stores_price_info_from_udb2_import(): void
    {
        $eventId = 'd2b41f1d-598c-46af-a3a5-10e373faa6fe';
        $cdbXml = $this->getSample('event_with_price_value_and_description.cdbxml.xml');

        $priceInfo = new PriceInfo(
            Tariff::createBasePrice(
                new Money(999, new Currency('EUR'))
            ),
            new Tariffs()
        );

        $this->scenario
            ->given(
                [
                    new EventImportedFromUDB2($eventId, $cdbXml, self::NS_CDBXML_3_2),
                ]
            )
            ->when(
                fn (Event $event) => $event->updatePriceInfo($priceInfo)
            )
            ->then([]);
    }

    /**
     * @test
     */
    public function it_handles_update_price_info_from_udb2_update(): void
    {
        $eventId = 'd2b41f1d-598c-46af-a3a5-10e373faa6fe';

        $this->scenario
            ->given(
                [
                    new EventImportedFromUDB2(
                        $eventId,
                        $this->getSample('event_without_price.cdbxml.xml'),
                        self::NS_CDBXML_3_2
                    ),
                    new EventUpdatedFromUDB2(
                        $eventId,
                        $this->getSample('event_with_price_value_and_formatted_description.cdbxml.xml'),
                        self::NS_CDBXML_3_2
                    ),
                ]
            )
            ->when(
                fn (Event $event) => $event->updatePriceInfo(
                    new PriceInfo(
                        Tariff::createBasePrice(new Money(1250, new Currency('EUR'))),
                        new Tariffs(
                            new Tariff(
                                new TranslatedTariffName(
                                    new Language('nl'),
                                    new TariffName('Met kinderen')
                                ),
                                new Money(2000, new Currency('EUR'))
                            ),
                        )
                    )
                )
            )
            ->when(
                fn (Event $event) => $event->updatePriceInfo(
                    new PriceInfo(
                        Tariff::createBasePrice(new Money(1250, new Currency('EUR'))),
                        new Tariffs(
                            new Tariff(
                                new TranslatedTariffName(
                                    new Language('nl'),
                                    new TariffName('Met kinderen')
                                ),
                                new Money(1499, new Currency('EUR'))
                            ),
                        )
                    )
                )
            )
            ->then([
                new PriceInfoUpdated(
                    $eventId,
                    new PriceInfo(
                        Tariff::createBasePrice(new Money(1250, new Currency('EUR'))),
                        new Tariffs(
                            new Tariff(
                                new TranslatedTariffName(
                                    new Language('nl'),
                                    new TariffName('Met kinderen')
                                ),
                                new Money(1499, new Currency('EUR'))
                            ),
                        )
                    )
                ),
            ]);
    }

    /**
     * @test
     */
    public function it_can_be_tagged_with_multiple_labels(): void
    {
        $this->scenario
            ->given([
                $this->getCreationEvent(),
            ])
            ->when(
                function (Event $event): void {
                    $event->addLabel(new Label(new LabelName('foo')));
                    $event->addLabel(new Label(new LabelName('bar')));
                }
            )
            ->then([
                new LabelAdded('d2b41f1d-598c-46af-a3a5-10e373faa6fe', 'foo'),
                new LabelAdded('d2b41f1d-598c-46af-a3a5-10e373faa6fe', 'bar'),
            ]);
    }

    /**
     * @test
     */
    public function it_only_applies_the_same_tag_once(): void
    {
        $this->scenario
            ->given([
                $this->getCreationEvent(),
            ])
            ->when(
                function (Event $event): void {
                    $event->addLabel(new Label(new LabelName('foo')));
                    $event->addLabel(new Label(new LabelName('foo')));
                }
            )
            ->then([
                new LabelAdded('d2b41f1d-598c-46af-a3a5-10e373faa6fe', 'foo'),
            ]);
    }

    /**
     * @test
     */
    public function it_does_not_add_similar_labels_with_different_letter_casing(): void
    {
        $this->scenario
            ->given([
                $this->getCreationEvent(),
            ])
            ->when(
                function (Event $event): void {
                    $event->addLabel(new Label(new LabelName('Foo')));
                    $event->addLabel(new Label(new LabelName('foo')));
                    $event->addLabel(new Label(new LabelName('België')));
                    $event->addLabel(new Label(new LabelName('BelgiË')));
                }
            )
            ->then([
                new LabelAdded('d2b41f1d-598c-46af-a3a5-10e373faa6fe', 'Foo'),
                new LabelAdded('d2b41f1d-598c-46af-a3a5-10e373faa6fe', 'België'),
            ]);
    }

    /**
     * @test
     */
    public function it_can_be_imported_from_udb2_cdbxml_and_takes_labels_into_account(): void
    {
        $xmlData = $this->getSample('EventTest.cdbxml.xml');
        $eventId = 'a2d50a8d-5b83-4c8b-84e6-e9c0bacbb1a3';
        $xmlNamespace = self::NS_CDBXML_3_2;

        $this->scenario
            ->given([
                new EventImportedFromUDB2($eventId, $xmlData, $xmlNamespace),
            ])
            ->when(
                function (Event $event): void {
                    $event->addLabel(new Label(new LabelName('kunst')));
                    $event->addLabel(new Label(new LabelName('tentoonstelling')));
                    $event->addLabel(new Label(new LabelName('brugge')));
                    $event->addLabel(new Label(new LabelName('grafiek')));
                    $event->addLabel(new Label(new LabelName('oud sint jan')));
                    $event->addLabel(new Label(new LabelName('TRAEGHE GENUINE ARTS')));
                    $event->addLabel(new Label(new LabelName('janine de conink')));
                    $event->addLabel(new Label(new LabelName('brugge oktober')));
                }
            )
            ->then([]);
    }

    /**
     * @test
     */
    public function it_can_import_same_label_with_correct_visibility_after_udb2_import_with_incorrect_visibility(): void
    {
        $xmlData = $this->getSample('EventTest.cdbxml.xml');
        $eventId = 'a2d50a8d-5b83-4c8b-84e6-e9c0bacbb1a3';
        $xmlNamespace = self::NS_CDBXML_3_2;

        $this->scenario
            ->given([
                new EventImportedFromUDB2($eventId, $xmlData, $xmlNamespace),
            ])
            ->when(
                function (Event $event): void {
                    // Do an import with 3 pre-existing labels with different visibility, 2 same labels, and 3 missing/
                    // removed labels that should be kept (since they were not added via a JSON import before).
                    $event->importLabels(
                        new Labels(
                            new Label(new LabelName('kunst'), false),
                            new Label(new LabelName('tentoonstelling'), false),
                            new Label(new LabelName('brugge'), false),
                            new Label(new LabelName('grafiek'), true),
                            new Label(new LabelName('TRAEGHE GENUINE ARTS'), true),
                        ),
                    );
                }
            )
            ->then([
                new LabelsImported(
                    $eventId,
                    [],
                    [
                        'kunst',
                        'tentoonstelling',
                        'brugge',
                    ]
                ),
                new LabelRemoved($eventId, 'kunst', true),
                new LabelRemoved($eventId, 'tentoonstelling', true),
                new LabelRemoved($eventId, 'brugge', true),
                new LabelAdded($eventId, 'kunst', false),
                new LabelAdded($eventId, 'tentoonstelling', false),
                new LabelAdded($eventId, 'brugge', false),
            ]);
    }

    public function unlabelDataProvider(): array
    {
        $label = new Label(new LabelName('foo'));

        $id = '004aea08-e13d-48c9-b9eb-a18f20e6d44e';
        $ns = self::NS_CDBXML_3_3;
        $cdbXml = $this->getSample('event_004aea08-e13d-48c9-b9eb-a18f20e6d44e.xml');
        $cdbXmlWithFooKeyword = $this->getSample('event_004aea08-e13d-48c9-b9eb-a18f20e6d44e_additional_keyword.xml');

        $eventImportedFromUdb2 = new EventImportedFromUDB2(
            $id,
            $cdbXml,
            $ns
        );

        return [
            'label added by udb3' => [
                $id,
                $label,
                [
                    $eventImportedFromUdb2,
                    new LabelAdded(
                        $id,
                        $label->getName()->toString(),
                        $label->isVisible()
                    ),
                ],
            ],
            'label added by update from udb2' => [
                $id,
                $label,
                [
                    $eventImportedFromUdb2,
                    new EventUpdatedFromUDB2(
                        $id,
                        $cdbXmlWithFooKeyword,
                        $ns
                    ),
                ],
            ],
            'label with different casing' => [
                $id,
                $label,
                [
                    $eventImportedFromUdb2,
                    new LabelAdded(
                        $id,
                        'fOO'
                    ),
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider unlabelDataProvider
     */
    public function it_can_be_unlabelled(string $id, Label $label, array $givens): void
    {
        $this->scenario
            ->given($givens)
            ->when(
                function (Event $event) use ($label): void {
                    $event->removeLabel($label->getName()->toString());
                }
            )
            ->then(
                [
                    new LabelRemoved($id, $label->getName()->toString(), $label->isVisible()),
                ]
            );
    }

    public function unlabelIgnoredDataProvider(): array
    {
        $label = new Label(new LabelName('foo'));

        $id = '004aea08-e13d-48c9-b9eb-a18f20e6d44e';
        $ns = self::NS_CDBXML_3_3;
        $cdbXml = $this->getSample('event_004aea08-e13d-48c9-b9eb-a18f20e6d44e.xml');
        $cdbXmlWithFooKeyword = $this->getSample('event_004aea08-e13d-48c9-b9eb-a18f20e6d44e_additional_keyword.xml');

        $eventImportedFromUdb2 = new EventImportedFromUDB2(
            $id,
            $cdbXml,
            $ns
        );

        return [
            'label not present in imported udb2 cdbxml' => [
                $label,
                [
                    $eventImportedFromUdb2,
                ],
            ],
            'label previously removed by an update from udb2' => [
                $label,
                [
                    new EventImportedFromUDB2(
                        $id,
                        $cdbXmlWithFooKeyword,
                        $ns
                    ),
                    new EventUpdatedFromUDB2(
                        $id,
                        $cdbXml,
                        $ns
                    ),
                ],
            ],
            'label previously removed' => [
                $label,
                [
                    $eventImportedFromUdb2,
                    new LabelAdded(
                        $id,
                        $label->getName()->toString(),
                        $label->isVisible()
                    ),
                    new LabelRemoved(
                        $id,
                        $label->getName()->toString(),
                        $label->isVisible()
                    ),
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider unlabelIgnoredDataProvider
     */
    public function it_silently_ignores_unlabel_request_if_label_is_not_present(Label $label, array $givens): void
    {
        $this->scenario
            ->given($givens)
            ->when(
                function (Event $event) use ($label): void {
                    $event->removeLabel($label->getName()->toString());
                }
            )
            ->then([]);
    }

    /**
     * @test
     */
    public function it_should_not_add_duplicate_images(): void
    {
        $image = new Image(
            new UUID('de305d54-75b4-431b-adb2-eb6b9e546014'),
            new MIMEType('image/png'),
            new Description('The Gleaners'),
            new CopyrightHolder('Jean-François Millet'),
            new Url('http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png'),
            new Language('en')
        );

        $cdbXml = SampleFiles::read(
            __DIR__ . '/samples/event_entryapi_valid_with_keywords.xml'
        );

        $this->scenario
            ->withAggregateId('004aea08-e13d-48c9-b9eb-a18f20e6d44e')
            ->given(
                [
                    new EventImportedFromUDB2(
                        '004aea08-e13d-48c9-b9eb-a18f20e6d44e',
                        $cdbXml,
                        \CultureFeed_Cdb_Xml::namespaceUriForVersion('3.3')
                    ),
                    new ImageAdded(
                        '004aea08-e13d-48c9-b9eb-a18f20e6d44e',
                        $image
                    ),
                ]
            )
            ->when(
                function (Event $event) use ($image): void {
                    $event->addImage(
                        $image
                    );
                }
            )
            ->then([]);
    }

    /**
     * @test
     */
    public function it_removes_images(): void
    {
        $cdbXml = SampleFiles::read(
            __DIR__ . '/samples/event_entryapi_valid_with_keywords.xml'
        );

        $image = new Image(
            new UUID('de305d54-75b4-431b-adb2-eb6b9e546014'),
            new MIMEType('image/png'),
            new Description('The Gleaners'),
            new CopyrightHolder('Jean-François Millet'),
            new Url('http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png'),
            new Language('en')
        );

        $this->scenario
            ->withAggregateId('004aea08-e13d-48c9-b9eb-a18f20e6d44e')
            ->given(
                [
                    new EventImportedFromUDB2(
                        '004aea08-e13d-48c9-b9eb-a18f20e6d44e',
                        $cdbXml,
                        \CultureFeed_Cdb_Xml::namespaceUriForVersion('3.3')
                    ),
                    new ImageAdded(
                        '004aea08-e13d-48c9-b9eb-a18f20e6d44e',
                        $image
                    ),
                ]
            )
            ->when(
                function (Event $event) use ($image): void {
                    $event->removeImage(
                        $image
                    );
                }
            )
            ->then(
                [
                    new ImageRemoved(
                        '004aea08-e13d-48c9-b9eb-a18f20e6d44e',
                        $image
                    ),
                ]
            );
    }

    /**
     * @test
     */
    public function it_silently_ignores_an_image_removal_request_when_image_is_not_present(): void
    {
        $cdbXml = SampleFiles::read(
            __DIR__ . '/samples/event_entryapi_valid_with_keywords.xml'
        );

        $image = new Image(
            new UUID('de305d54-75b4-431b-adb2-eb6b9e546014'),
            new MIMEType('image/png'),
            new Description('The Gleaners'),
            new CopyrightHolder('Jean-François Millet'),
            new Url('http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png'),
            new Language('en')
        );

        $this->scenario
            ->withAggregateId('004aea08-e13d-48c9-b9eb-a18f20e6d44e')
            ->given(
                [
                    new EventImportedFromUDB2(
                        '004aea08-e13d-48c9-b9eb-a18f20e6d44e',
                        $cdbXml,
                        \CultureFeed_Cdb_Xml::namespaceUriForVersion('3.3')
                    ),
                ]
            )
            ->when(
                function (Event $event) use ($image): void {
                    $event->removeImage(
                        $image
                    );
                }
            )
            ->then([]);
    }

    /**
     * @test
     */
    public function it_handles_update_location(): void
    {
        $eventId = 'd2b41f1d-598c-46af-a3a5-10e373faa6fe';
        $createEvent = $this->getCreationEvent();
        $oldLocationId = $createEvent->getLocation();
        $newLocationId = new LocationId('57738178-28a5-4afb-90c0-fd0beba172a8');

        $this->scenario
            ->given(
                [
                    $createEvent,
                ]
            )
            ->when(
                function (Event $event) use ($oldLocationId, $newLocationId): void {
                    $event->updateLocation($oldLocationId);
                    $event->updateLocation($newLocationId);
                    $event->updateLocation($newLocationId);
                }
            )
            ->then(
                [
                    new LocationUpdated($eventId, $newLocationId),
                ]
            );
    }

    /**
     * @test
     */
    public function it_handles_update_location_after_udb2_import(): void
    {
        $eventId = 'd2b41f1d-598c-46af-a3a5-10e373faa6fe';
        $createEvent = $this->getCreationEvent();
        $locationId = new LocationId($createEvent->getLocation()->toString());

        $xmlData = $this->getSample('EventTest.cdbxml.xml');
        $xmlNamespace = self::NS_CDBXML_3_2;

        $this->scenario
            ->given(
                [
                    $createEvent,
                    new LocationUpdated($eventId, $locationId),
                    new EventImportedFromUDB2($eventId, $xmlData, $xmlNamespace),
                ]
            )
            ->when(
                function (Event $event) use ($locationId): void {
                    $event->updateLocation($locationId);
                }
            )
            ->then(
                [
                    new LocationUpdated($eventId, $locationId),
                ]
            );
    }

    /**
     * @test
     */
    public function it_throws_when_updating_online_event_to_real_location(): void
    {
        $eventId = 'd2b41f1d-598c-46af-a3a5-10e373faa6fe';
        $createEvent = $this->getCreationEvent();
        $locationId = new LocationId('57738178-28a5-4afb-90c0-fd0beba172a8');

        $this->expectException(AttendanceModeNotSupported::class);
        $this->expectExceptionMessage(
            'Cannot update the location of an online event to a physical location. Set the attendanceMode to mixed or offline first.'
        );

        $this->scenario
            ->given(
                [
                    $createEvent,
                    new AttendanceModeUpdated($eventId, AttendanceMode::online()->toString()),
                ]
            )
            ->when(
                fn (Event $event) => $event->updateLocation($locationId)
            )
            ->then([]);
    }

    /**
     * @test
     */
    public function it_sets_the_audience_type_to_education_when_setting_a_dummy_education_location(): void
    {
        $createEvent = $this->getCreationEvent();
        $eventId = $createEvent->getEventId();
        $newLocationId = new LocationId('776fd571-9830-42f0-81be-a38eac5506ce');
        LocationId::setDummyPlaceForEducationIds([$newLocationId->toString()]);

        $this->scenario
            ->given(
                [
                    $createEvent,
                ]
            )
            ->when(
                function (Event $event) use ($newLocationId): void {
                    $event->updateLocation($newLocationId);
                }
            )
            ->then(
                [
                    new LocationUpdated($eventId, $newLocationId),
                    new AudienceUpdated($eventId, AudienceType::education()),
                ]
            );
    }

    /**
     * @test
     * @dataProvider audienceDataProvider
     * @param AudienceType[] $audienceTypes
     * @param AudienceUpdated[] $audienceUpdatedEvents
     */
    public function it_applies_the_audience_type(
        array $audienceTypes,
        array $audienceUpdatedEvents
    ): void {
        $this->scenario
            ->given([
                $this->getCreationEvent(),
            ])
            ->when(
                function (Event $event) use ($audienceTypes): void {
                    foreach ($audienceTypes as $audienceType) {
                        $event->updateAudience($audienceType);
                    }
                }
            )
            ->then(
                $audienceUpdatedEvents
            );
    }

    public function audienceDataProvider(): array
    {
        $eventId = 'd2b41f1d-598c-46af-a3a5-10e373faa6fe';

        return [
            'single audience type' =>
                [
                    [
                        AudienceType::members(),
                    ],
                    [
                        new AudienceUpdated(
                            $eventId,
                            AudienceType::members()
                        ),
                    ],
                ],
            'multiple audience types' =>
                [
                    [
                        AudienceType::members(),
                        AudienceType::everyone(),
                    ],
                    [
                        new AudienceUpdated(
                            $eventId,
                            AudienceType::members()
                        ),
                        new AudienceUpdated(
                            $eventId,
                            AudienceType::everyone()
                        ),
                    ],
                ],
            'equal audience types' =>
                [
                    [
                        AudienceType::members(),
                        AudienceType::members(),
                    ],
                    [
                        new AudienceUpdated(
                            $eventId,
                            AudienceType::members()
                        ),
                    ],
                ],
        ];
    }

    /**
     * @test
     */
    public function it_will_not_update_audience_for_events_with_dummy_place(): void
    {
        $eventId = 'd2b41f1d-598c-46af-a3a5-10e373faa6fe';
        $dummyLocationId = 'b1fe77f8-960a-4a5e-8a45-fcfe2db3c497';
        LocationId::setDummyPlaceForEducationIds([$dummyLocationId]);
        $this->expectException(IncompatibleAudienceType::class);
        $this->scenario
            ->given([
                $this->getCreationEvent(),
                new AudienceUpdated($eventId, AudienceType::education()),
                new LocationUpdated($eventId, new LocationId($dummyLocationId)),
            ])
            ->when(
                function (Event $event): void {
                    $event->updateAudience(AudienceType::everyone());
                }
            )
            ->then([]);
    }

    /**
     * @test
     * @group issue-III-1380
     */
    public function it_refuses_to_copy_when_there_are_uncommitted_events(): void
    {
        $event = $this->event;

        $this->expectException(RuntimeException::class);

        $event->copy(
            'e49430ca-5729-4768-8364-02ddb385517a',
            new Calendar(
                CalendarType::permanent()
            )
        );
    }

    /**
     * @test
     * @group issue-III-1380
     */
    public function it_resets_labels_on_copy(): void
    {
        $newEventId = 'e49430ca-5729-4768-8364-02ddb385517a';
        $calendar = new Calendar(
            CalendarType::permanent()
        );
        $label = new Label(new LabelName('ABC'));

        $event = $this->event;
        $event->addLabel($label);

        $event->getUncommittedEvents();

        $this->scenario
            ->when(function () use ($event, $newEventId, $calendar, $label) {
                $newEvent = $event->copy(
                    $newEventId,
                    $calendar
                );

                $newEvent->addLabel($label);

                return $newEvent;
            })
            ->then(
                [
                    new EventCopied(
                        $newEventId,
                        'foo',
                        $calendar
                    ),
                    new LabelAdded(
                        $newEventId,
                        $label->getName()->toString(),
                        $label->isVisible()
                    ),
                ]
            );
    }

    /**
     * @test
     * @group issue-III-1380
     */
    public function it_keeps_audience_on_copy(): void
    {
        $newEventId = 'e49430ca-5729-4768-8364-02ddb385517a';
        $calendar = new Calendar(
            CalendarType::permanent()
        );
        $audience = AudienceType::education();

        $event = $this->event;
        $event->updateAudience($audience);

        $event->getUncommittedEvents();

        $this->scenario
            ->when(function () use ($event, $newEventId, $calendar, $audience) {
                $newEvent = $event->copy(
                    $newEventId,
                    $calendar
                );

                $newEvent->updateAudience($audience);

                return $newEvent;
            })
            ->then(
                [
                    new EventCopied(
                        $newEventId,
                        'foo',
                        $calendar
                    ),
                ]
            );
    }

    /**
     * @test
     * @group issue-III-1380
     */
    public function it_resets_workflow_status_on_copy(): void
    {
        $newEventId = 'e49430ca-5729-4768-8364-02ddb385517a';
        $calendar = new Calendar(
            CalendarType::permanent()
        );

        $publicationDate = new \DateTimeImmutable();

        $event = $this->event;
        $event->publish($publicationDate);

        $event->getUncommittedEvents();

        $newPublicationDate = new \DateTimeImmutable('+3 days');

        $this->scenario
            ->when(function () use ($event, $newEventId, $calendar, $newPublicationDate) {
                $newEvent = $event->copy(
                    $newEventId,
                    $calendar
                );

                $newEvent->publish($newPublicationDate);

                return $newEvent;
            })
            ->then(
                [
                    new EventCopied(
                        $newEventId,
                        'foo',
                        $calendar
                    ),
                    new Published(
                        $newEventId,
                        $newPublicationDate
                    ),
                ]
            );
    }

    /**
     * @test
     */
    public function it_does_not_update_the_same_title_after_event_created(): void
    {
        $this->scenario
            ->withAggregateId('d2b41f1d-598c-46af-a3a5-10e373faa6fe')
            ->given([
                $this->getCreationEvent(),
            ])
            ->when(
                function (Event $event): void {
                    $event->updateTitle(
                        new Language('en'),
                        new Title('some representative title')
                    );
                }
            )
            ->then([]);
    }

    /**
     * @test
     */
    public function it_does_not_update_the_same_calendar_after_event_created(): void
    {
        $this->scenario
            ->withAggregateId('d2b41f1d-598c-46af-a3a5-10e373faa6fe')
            ->given([
                $this->getCreationEvent(),
            ])
            ->when(
                function (Event $event): void {
                    $event->updateCalendar(
                        new Calendar(CalendarType::permanent())
                    );
                }
            )
            ->then([]);
    }

    /**
     * @test
     */
    public function it_does_not_update_the_same_audience_type_after_event_created(): void
    {
        $this->scenario
            ->withAggregateId('d2b41f1d-598c-46af-a3a5-10e373faa6fe')
            ->given([
                $this->getCreationEvent(),
            ])
            ->when(
                function (Event $event): void {
                    $event->updateAudience(
                        AudienceType::everyone()
                    );
                }
            )
            ->then([]);
    }

    /**
     * @test
     */
    public function it_does_not_update_the_same_contact_point_after_event_created(): void
    {
        $this->scenario
            ->withAggregateId('d2b41f1d-598c-46af-a3a5-10e373faa6fe')
            ->given([
                $this->getCreationEvent(),
            ])
            ->when(
                function (Event $event): void {
                    $event->updateContactPoint(
                        new ContactPoint()
                    );
                }
            )
            ->then([]);
    }

    /**
     * @test
     */
    public function it_does_not_update_the_same_booking_info_after_event_created(): void
    {
        $this->scenario
            ->withAggregateId('d2b41f1d-598c-46af-a3a5-10e373faa6fe')
            ->given([
                $this->getCreationEventWithTheme(),
            ])
            ->when(
                function (Event $event): void {
                    $event->updateBookingInfo(
                        new BookingInfo()
                    );
                }
            )
            ->then([]);
    }

    /**
     * @test
     */
    public function it_ignores_uitpas_tariffs_when_price_info_is_missing(): void
    {
        $this->scenario
            ->withAggregateId('d2b41f1d-598c-46af-a3a5-10e373faa6fe')
            ->given([$this->getCreationEvent()])
            ->when(
                function (Event $event): void {
                    $event->updateUiTPASPrices(
                        new Tariffs(
                            new Tariff(
                                new TranslatedTariffName(
                                    new Language('nl'),
                                    new TariffName('Tariff 1')
                                ),
                                new Money(
                                    199,
                                    new Currency('EUR')
                                )
                            ),
                            new Tariff(
                                new TranslatedTariffName(
                                    new Language('nl'),
                                    new TariffName('Tariff 2')
                                ),
                                new Money(
                                    299,
                                    new Currency('EUR')
                                )
                            )
                        )
                    );
                }
            )
            ->then([]);
    }

    /**
     * @test
     */
    public function it_ignores_equal_uitpas_prices(): void
    {
        $this->scenario
            ->withAggregateId('d2b41f1d-598c-46af-a3a5-10e373faa6fe')
            ->given([
                $this->getCreationEvent(),
                new PriceInfoUpdated(
                    'd2b41f1d-598c-46af-a3a5-10e373faa6fe',
                    (new PriceInfo(
                        Tariff::createBasePrice(new Money(100, new Currency('EUR'))),
                        new Tariffs()
                    ))->withUiTPASTariffs(
                        new Tariffs(
                            new Tariff(
                                new TranslatedTariffName(
                                    new Language('nl'),
                                    new TariffName('Tariff 1')
                                ),
                                new Money(
                                    199,
                                    new Currency('EUR')
                                )
                            ),
                            new Tariff(
                                new TranslatedTariffName(
                                    new Language('nl'),
                                    new TariffName('Tariff 2')
                                ),
                                new Money(
                                    299,
                                    new Currency('EUR')
                                )
                            )
                        )
                    )
                ),
            ])
            ->when(
                function (Event $event): void {
                    $event->updateUiTPASPrices(
                        new Tariffs(
                            new Tariff(
                                new TranslatedTariffName(
                                    new Language('nl'),
                                    new TariffName('Tariff 1')
                                ),
                                new Money(
                                    199,
                                    new Currency('EUR')
                                )
                            ),
                            new Tariff(
                                new TranslatedTariffName(
                                    new Language('nl'),
                                    new TariffName('Tariff 2')
                                ),
                                new Money(
                                    299,
                                    new Currency('EUR')
                                )
                            )
                        )
                    );
                }
            )
            ->then([]);
    }

    /**
     * @test
     */
    public function it_triggers_price_info_updated_when_uitpas_prices_are_different(): void
    {
        $this->scenario
            ->withAggregateId('d2b41f1d-598c-46af-a3a5-10e373faa6fe')
            ->given([
                $this->getCreationEvent(),
                new PriceInfoUpdated(
                    'd2b41f1d-598c-46af-a3a5-10e373faa6fe',
                    new PriceInfo(
                        Tariff::createBasePrice(new Money(100, new Currency('EUR'))),
                        new Tariffs()
                    )
                ),
            ])
            ->when(
                function (Event $event): void {
                    $event->updateUiTPASPrices(
                        new Tariffs(
                            new Tariff(
                                new TranslatedTariffName(
                                    new Language('nl'),
                                    new TariffName('Tariff 1')
                                ),
                                new Money(
                                    199,
                                    new Currency('EUR')
                                )
                            ),
                            new Tariff(
                                new TranslatedTariffName(
                                    new Language('nl'),
                                    new TariffName('Tariff 2')
                                ),
                                new Money(
                                    299,
                                    new Currency('EUR')
                                )
                            )
                        )
                    );
                }
            )
            ->then([
                new PriceInfoUpdated(
                    'd2b41f1d-598c-46af-a3a5-10e373faa6fe',
                    (new PriceInfo(
                        Tariff::createBasePrice(new Money(100, new Currency('EUR'))),
                        new Tariffs()
                    ))->withUiTPASTariffs(
                        new Tariffs(
                            new Tariff(
                                new TranslatedTariffName(
                                    new Language('nl'),
                                    new TariffName('Tariff 1')
                                ),
                                new Money(
                                    199,
                                    new Currency('EUR')
                                )
                            ),
                            new Tariff(
                                new TranslatedTariffName(
                                    new Language('nl'),
                                    new TariffName('Tariff 2')
                                ),
                                new Money(
                                    299,
                                    new Currency('EUR')
                                )
                            )
                        )
                    )
                ),
            ]);
    }

    protected function getSample(string $file): string
    {
        return SampleFiles::read(
            __DIR__ . '/samples/' . $file
        );
    }
}
