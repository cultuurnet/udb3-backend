<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use Broadway\Domain\DomainMessage;
use Broadway\EventSourcing\Testing\AggregateRootScenarioTestCase;
use CultuurNet\UDB3\BookingInfo;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\ContactPoint;
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
use CultuurNet\UDB3\Event\Events\Moderation\Published;
use CultuurNet\UDB3\Event\Events\PriceInfoUpdated;
use CultuurNet\UDB3\Event\Events\TypicalAgeRangeDeleted;
use CultuurNet\UDB3\Event\Events\TypicalAgeRangeUpdated;
use CultuurNet\UDB3\Event\ValueObjects\Audience;
use CultuurNet\UDB3\Facility;
use CultuurNet\UDB3\Label as LegacyLabel;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Media\Properties\Description;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use CultuurNet\UDB3\Model\ValueObject\Audience\Age;
use CultuurNet\UDB3\Model\ValueObject\Audience\AudienceType;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use CultuurNet\UDB3\Model\ValueObject\Virtual\AttendanceMode;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Offer\AgeRange;
use CultuurNet\UDB3\PriceInfo\BasePrice;
use CultuurNet\UDB3\PriceInfo\PriceInfo;
use CultuurNet\UDB3\Theme;
use CultuurNet\UDB3\Title;
use CultuurNet\UDB3\ValueObject\MultilingualString;
use DateTimeInterface;
use Money\Currency;
use Money\Money;
use RuntimeException;
use CultuurNet\UDB3\StringLiteral;

class EventTest extends AggregateRootScenarioTestCase
{
    public const NS_CDBXML_3_2 = 'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL';
    public const NS_CDBXML_3_3 = 'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL';

    /**
     * @inheritdoc
     */
    protected function getAggregateRootClass()
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
            new Calendar(CalendarType::PERMANENT())
        );
    }

    private function getCreationEvent(): EventCreated
    {
        return new EventCreated(
            'd2b41f1d-598c-46af-a3a5-10e373faa6fe',
            new Language('en'),
            new Title('some representative title'),
            new EventType('0.50.4.0.0', 'concert'),
            new LocationId('322d67b6-e84d-4649-9384-12ecad74eab3'),
            new Calendar(CalendarType::PERMANENT())
        );
    }

    private function getCreationEventWithTheme(): EventCreated
    {
        return new EventCreated(
            'd2b41f1d-598c-46af-a3a5-10e373faa6fe',
            new Language('en'),
            new Title('some representative title'),
            new EventType('0.50.4.0.0', 'concert'),
            new LocationId('59400d1e-6f98-4da9-ab08-f58adceb7204'),
            new Calendar(CalendarType::PERMANENT()),
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
            new Calendar(CalendarType::PERMANENT())
        );

        $expectedEvent = new AudienceUpdated($eventUuid, new Audience(AudienceType::education()));

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
            CalendarType::PERMANENT()
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
                function (Event $event) use ($facilities) {
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
            ['016/101010'],
            ['test@2dotstwice.be', 'admin@2dotstwice.be'],
            ['http://www.2dotstwice.be']
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
                function (Event $event) use ($contactPoint) {
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
            CalendarType::PERIODIC(),
            \DateTime::createFromFormat(DateTimeInterface::ATOM, '2020-01-26T11:11:11+01:00'),
            \DateTime::createFromFormat(DateTimeInterface::ATOM, '2020-01-27T12:12:12+01:00')
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
                function (Event $event) use ($calendar) {
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
                function (Event $event) use ($typicalAgeRange, $otherTypicalAgeRange) {
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
                function (Event $event) {
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
            new MultilingualString(new Language('nl'), new StringLiteral('publiq')),
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
                function (Event $event) use ($bookingInfo) {
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
    public function it_handles_update_price_info_after_udb2_import(): void
    {
        $eventId = 'd2b41f1d-598c-46af-a3a5-10e373faa6fe';
        $createEvent = $this->getCreationEvent();

        $priceInfo = new PriceInfo(
            new BasePrice(
                new Money(1000, new Currency('EUR'))
            )
        );

        $xmlData = $this->getSample('EventTest.cdbxml.xml');
        $xmlNamespace = self::NS_CDBXML_3_2;

        $this->scenario
            ->given(
                [
                    $createEvent,
                    new PriceInfoUpdated($eventId, $priceInfo),
                    new EventUpdatedFromUDB2($eventId, $xmlData, $xmlNamespace),
                ]
            )
            ->when(
                function (Event $event) use ($priceInfo) {
                    $event->updatePriceInfo($priceInfo);
                }
            )
            ->then(
                [
                    new PriceInfoUpdated($eventId, $priceInfo),
                ]
            );
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
                function (Event $event) {
                    $event->addLabel(new Label(new LabelName('foo')));
                    $event->addLabel(new Label(new LabelName('bar')));
                }
            )
            ->then([
                new LabelAdded('d2b41f1d-598c-46af-a3a5-10e373faa6fe', new LegacyLabel('foo')),
                new LabelAdded('d2b41f1d-598c-46af-a3a5-10e373faa6fe', new LegacyLabel('bar')),
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
                function (Event $event) {
                    $event->addLabel(new Label(new LabelName('foo')));
                    $event->addLabel(new Label(new LabelName('foo')));
                }
            )
            ->then([
                new LabelAdded('d2b41f1d-598c-46af-a3a5-10e373faa6fe', new LegacyLabel('foo')),
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
                function (Event $event) {
                    $event->addLabel(new Label(new LabelName('Foo')));
                    $event->addLabel(new Label(new LabelName('foo')));
                    $event->addLabel(new Label(new LabelName('België')));
                    $event->addLabel(new Label(new LabelName('BelgiË')));
                }
            )
            ->then([
                new LabelAdded('d2b41f1d-598c-46af-a3a5-10e373faa6fe', new LegacyLabel('Foo')),
                new LabelAdded('d2b41f1d-598c-46af-a3a5-10e373faa6fe', new LegacyLabel('België')),
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
                function (Event $event) {
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
                function (Event $event) {
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
                    new Labels(
                        new Label(new LabelName('kunst'), false),
                        new Label(new LabelName('tentoonstelling'), false),
                        new Label(new LabelName('brugge'), false),
                    )
                ),
                new LabelRemoved($eventId, new LegacyLabel('kunst', true)),
                new LabelRemoved($eventId, new LegacyLabel('tentoonstelling', true)),
                new LabelRemoved($eventId, new LegacyLabel('brugge', true)),
                new LabelAdded($eventId, new LegacyLabel('kunst', false)),
                new LabelAdded($eventId, new LegacyLabel('tentoonstelling', false)),
                new LabelAdded($eventId, new LegacyLabel('brugge', false)),
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
                        new LegacyLabel($label->getName()->toString(), $label->isVisible())
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
                        new LegacyLabel('fOO')
                    ),
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider unlabelDataProvider
     */
    public function it_can_be_unlabelled(
        string $id,
        Label $label,
        array $givens
    ): void {
        $this->scenario
            ->given($givens)
            ->when(
                function (Event $event) use ($label) {
                    $event->removeLabel($label);
                }
            )
            ->then(
                [
                    new LabelRemoved($id, new LegacyLabel($label->getName()->toString(), $label->isVisible())),
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
                        new LegacyLabel($label->getName()->toString(), $label->isVisible())
                    ),
                    new LabelRemoved(
                        $id,
                        new LegacyLabel($label->getName()->toString(), $label->isVisible())
                    ),
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider unlabelIgnoredDataProvider
     */
    public function it_silently_ignores_unlabel_request_if_label_is_not_present(
        Label $label,
        array       $givens
    ): void {
        $this->scenario
            ->given($givens)
            ->when(
                function (Event $event) use ($label) {
                    $event->removeLabel($label);
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

        $cdbXml = file_get_contents(
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
                function (Event $event) use ($image) {
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
        $cdbXml = file_get_contents(
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
                function (Event $event) use ($image) {
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
        $cdbXml = file_get_contents(
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
                function (Event $event) use ($image) {
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
                function (Event $event) use ($oldLocationId, $newLocationId) {
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
                function (Event $event) use ($locationId) {
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

        $this->expectException(UpdateLocationNotSupported::class);
        $this->expectExceptionMessage(
            'Instead of passing the real location for this online event, please update the attendance mode to offline or mixed.'
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
                function (Event $event) use ($newLocationId) {
                    $event->updateLocation($newLocationId);
                }
            )
            ->then(
                [
                    new LocationUpdated($eventId, $newLocationId),
                    new AudienceUpdated($eventId, new Audience(AudienceType::education())),
                ]
            );
    }

    /**
     * @test
     * @dataProvider audienceDataProvider
     * @param Audience[] $audiences
     * @param AudienceUpdated[] $audienceUpdatedEvents
     */
    public function it_applies_the_audience_type(
        array $audiences,
        array $audienceUpdatedEvents
    ): void {
        $this->scenario
            ->given([
                $this->getCreationEvent(),
            ])
            ->when(
                function (Event $event) use ($audiences) {
                    foreach ($audiences as $audience) {
                        $event->updateAudience($audience);
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
                        new Audience(AudienceType::members()),
                    ],
                    [
                        new AudienceUpdated(
                            $eventId,
                            new Audience(AudienceType::members())
                        ),
                    ],
                ],
            'multiple audience types' =>
                [
                    [
                        new Audience(AudienceType::members()),
                        new Audience(AudienceType::everyone()),
                    ],
                    [
                        new AudienceUpdated(
                            $eventId,
                            new Audience(AudienceType::members())
                        ),
                        new AudienceUpdated(
                            $eventId,
                            new Audience(AudienceType::everyone())
                        ),
                    ],
                ],
            'equal audience types' =>
                [
                    [
                        new Audience(AudienceType::members()),
                        new Audience(AudienceType::members()),
                    ],
                    [
                        new AudienceUpdated(
                            $eventId,
                            new Audience(AudienceType::members())
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
                new AudienceUpdated($eventId, new Audience(AudienceType::education())),
                new LocationUpdated($eventId, new LocationId($dummyLocationId)),
            ])
            ->when(
                function (Event $event) {
                    $event->updateAudience(new Audience(AudienceType::everyone()));
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
                CalendarType::PERMANENT()
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
            CalendarType::PERMANENT()
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
                        new LegacyLabel($label->getName()->toString(), $label->isVisible())
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
            CalendarType::PERMANENT()
        );
        $audience = new Audience(AudienceType::education());

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
            CalendarType::PERMANENT()
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
                function (Event $event) {
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
                function (Event $event) {
                    $event->updateCalendar(
                        new Calendar(CalendarType::PERMANENT())
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
                function (Event $event) {
                    $event->updateAudience(
                        new Audience(AudienceType::everyone())
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
                function (Event $event) {
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
                function (Event $event) {
                    $event->updateBookingInfo(
                        new BookingInfo()
                    );
                }
            )
            ->then([]);
    }

    protected function getSample(string $file): string
    {
        return file_get_contents(
            __DIR__ . '/samples/' . $file
        );
    }
}
