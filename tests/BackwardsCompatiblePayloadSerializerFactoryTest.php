<?php

declare(strict_types=1);

namespace CultuurNet\UDB3;

use Broadway\Serializer\Serializer;
use CultuurNet\UDB3\Event\Events\BookingInfoUpdated;
use CultuurNet\UDB3\Event\Events\ContactPointUpdated;
use CultuurNet\UDB3\Event\Events\DescriptionTranslated as EventDescriptionTranslated;
use CultuurNet\UDB3\Event\Events\DescriptionUpdated as EventDescriptionUpdated;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\EventImportedFromUDB2;
use CultuurNet\UDB3\Event\Events\LabelAdded;
use CultuurNet\UDB3\Event\Events\LabelRemoved;
use CultuurNet\UDB3\Event\Events\MajorInfoUpdated;
use CultuurNet\UDB3\Event\Events\PriceInfoUpdated;
use CultuurNet\UDB3\Event\Events\TitleTranslated;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Label\Events\AbstractEvent;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Entity;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Model\ValueObject\Contact\ContactPoint;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumber;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumbers;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Price\PriceInfo;
use CultuurNet\UDB3\Model\ValueObject\Price\Tariff;
use CultuurNet\UDB3\Model\ValueObject\Price\Tariffs;
use CultuurNet\UDB3\Model\ValueObject\Text\Description;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Price\TariffName;
use CultuurNet\UDB3\Model\ValueObject\Price\TranslatedTariffName;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddresses;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Model\ValueObject\Web\Urls;
use CultuurNet\UDB3\Offer\Events\AbstractDescriptionTranslated;
use CultuurNet\UDB3\Offer\Events\AbstractDescriptionUpdated;
use CultuurNet\UDB3\Offer\Events\AbstractLabelEvent;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreatedWithUniqueWebsite;
use CultuurNet\UDB3\Place\Events\DescriptionTranslated as PlaceDescriptionTranslated;
use CultuurNet\UDB3\Place\Events\DescriptionUpdated as PlaceDescriptionUpdated;
use CultuurNet\UDB3\Place\Events\PlaceCreated;
use CultuurNet\UDB3\Role\Events\ConstraintAdded;
use CultuurNet\UDB3\ValueObject\MultilingualString;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BackwardsCompatiblePayloadSerializerFactoryTest extends TestCase
{
    protected Serializer $serializer;

    /**
     * @var ReadRepositoryInterface&MockObject
     */
    private $labelRepository;

    private string $sampleDir;

    public function setUp(): void
    {
        parent::setUp();

        $this->labelRepository = $this->createMock(ReadRepositoryInterface::class);
        $this->labelRepository->method('getByUuid')
            ->with(new UUID('86c5b0f4-a5da-4a81-815f-3839634c212c'))
            ->willReturn(
                new Entity(
                    new UUID('86c5b0f4-a5da-4a81-815f-3839634c212c'),
                    '2dotstwice',
                    Visibility::INVISIBLE(),
                    Privacy::PRIVACY_PRIVATE()
                )
            );

        $this->serializer = BackwardsCompatiblePayloadSerializerFactory::createSerializer(
            $this->labelRepository
        );

        $this->sampleDir = __DIR__ . '/samples/';
    }

    /**
     * @test
     * @dataProvider mainLanguageDataProvider
     */
    public function it_handles_main_language(
        string $sampleFile,
        Language $expectedMainLanguage
    ): void {
        $serialized = SampleFiles::read($sampleFile);
        $decoded = Json::decodeAssociatively($serialized);

        /** @var EventCreated|PlaceCreated|OrganizerCreatedWithUniqueWebsite $created */
        $created = $this->serializer->deserialize($decoded);

        // The signature of getMainLanguage() is not consistent, sometimes this returns a string, sometimes a Language object
        if (is_string($created->getMainLanguage())) {
            $this->assertEquals($expectedMainLanguage->toString(), $created->getMainLanguage());
        } else {
            $this->assertTrue($expectedMainLanguage->sameAs($created->getMainLanguage()));
        }
    }

    public function mainLanguageDataProvider(): array
    {
        return [
            'EventCreated no main language' => [
                __DIR__ . '/samples/serialized_event_event_created_class.json',
                new Language('nl'),
            ],
            'PlaceCreated no main language' => [
                __DIR__ . '/samples/serialized_event_place_created_class.json',
                new Language('nl'),
            ],
            'OrganizerCreatedWithUniqueWebsite no main language' => [
                __DIR__ . '/samples/serialized_event_organizer_created_with_unique_website_class.json',
                new Language('nl'),
            ],
            'EventCreated with es as main language' => [
                __DIR__ . '/samples/serialized_event_event_created_with_main_language_class.json',
                new Language('es'),
            ],
            'PlaceCreated with es as main language' => [
                __DIR__ . '/samples/serialized_event_place_created_with_main_language_class.json',
                new Language('es'),
            ],
            'OrganizerCreatedWithUniqueWebsite with es as main language' => [
                __DIR__ . '/samples/serialized_event_organizer_created_with_unique_website_and_main_language.class.json',
                new Language('es'),
            ],
        ];
    }

    /**
     * @test
     */
    public function it_transforms_a_serialized_event_created_location_to_an_id(): void
    {
        $serialized = SampleFiles::read(__DIR__ . '/samples/serialized_event_event_created_class.json');
        $decoded = Json::decodeAssociatively($serialized);

        /** @var EventCreated $created */
        $created = $this->serializer->deserialize($decoded);

        $this->assertEquals(new LocationId('54131948-ffb9-4973-b528-800590265be5'), $created->getLocation());
    }

    /**
     * @test
     */
    public function it_transforms_a_serialized_major_info_updated_location_to_an_id(): void
    {
        $serialized = SampleFiles::read(__DIR__ . '/samples/serialized_event_major_info_updated_class.json');
        $decoded = Json::decodeAssociatively($serialized);

        /** @var MajorInfoUpdated $majorInfoUpdated */
        $majorInfoUpdated = $this->serializer->deserialize($decoded);

        $this->assertEquals(new LocationId('061C13AC-A15F-F419-D8993D68C9E94548'), $majorInfoUpdated->getLocation());
    }

    /**
     * @test
     */
    public function it_knows_the_new_namespace_of_event_title_translated(): void
    {
        $sampleFile = $this->sampleDir . 'serialized_event_title_translated_class.json';
        $this->assertClass($sampleFile, TitleTranslated::class);
    }

    /**
     * @test
     */
    public function it_replaces_event_id_with_item_id_on_event_title_translated(): void
    {
        $sampleFile = $this->sampleDir . 'serialized_event_title_translated_class.json';
        $this->assertEventIdReplacedWithItemId($sampleFile);
    }

    /**
     * @test
     */
    public function it_knows_the_new_namespace_of_event_description_translated(): void
    {
        $sampleFile = $this->sampleDir . 'serialized_event_description_translated_class.json';
        $this->assertClass($sampleFile, EventDescriptionTranslated::class);
    }

    /**
     * @test
     */
    public function it_replaces_event_id_with_item_id_on_event_description_translated(): void
    {
        $sampleFile = $this->sampleDir . 'serialized_event_description_translated_class.json';
        $this->assertEventIdReplacedWithItemId($sampleFile);
    }

    /**
     * @test
     */
    public function it_adds_label_name_on_made_invisible_event(): void
    {
        $sampleFile = $this->sampleDir . 'serialized_label_was_made_invisible.json';
        $this->assertLabelNameAdded($sampleFile);
    }

    /**
     * @test
     */
    public function it_adds_label_name_on_made_visible_event(): void
    {
        $sampleFile = $this->sampleDir . 'serialized_label_was_made_visible.json';
        $this->assertLabelNameAdded($sampleFile);
    }

    /**
     * @test
     */
    public function it_adds_label_name_on_made_private_event(): void
    {
        $sampleFile = $this->sampleDir . 'serialized_label_was_made_private.json';
        $this->assertLabelNameAdded($sampleFile);
    }

    /**
     * @test
     */
    public function it_adds_label_name_on_made_public_event(): void
    {
        $sampleFile = $this->sampleDir . 'serialized_label_was_made_public.json';
        $this->assertLabelNameAdded($sampleFile);
    }

    /**
     * @test
     */
    public function it_adds_label_name_and_visibility_on_label_added_to_organizer_event(): void
    {
        $sampleFile = $this->sampleDir . 'serialized_label_was_added_to_organizer.json';
        $this->assertOrganizerLabelEventFixed($sampleFile);
    }

    /**
     * @test
     */
    public function it_adds_label_visibility_on_label_added_to_organizer_event_with_label(): void
    {
        $sampleFile = $this->sampleDir . 'serialized_label_was_added_to_organizer_with_label.json';
        $this->assertOrganizerLabelEventFixed($sampleFile);
    }

    /**
     * @test
     */
    public function it_adds_label_name_on_label_added_to_organizer_event_with_visibility(): void
    {
        $sampleFile = $this->sampleDir . 'serialized_label_was_added_to_organizer_with_visibility.json';
        $this->assertOrganizerLabelEventFixed($sampleFile);
    }

    /**
     * @test
     */
    public function it_does_not_modify_label_added_to_organizer_event_with_label_and_visibility(): void
    {
        $sampleFile = $this->sampleDir . 'serialized_label_was_added_to_organizer_with_label_and_visibility.json';

        $this->labelRepository->expects($this->never())
            ->method('getByUuid');

        $serialized = SampleFiles::read($sampleFile);
        $decoded = Json::decodeAssociatively($serialized);
        $this->serializer->deserialize($decoded);
    }

    /**
     * @test
     */
    public function it_adds_label_name_and_visibility_on_label_removed_from_organizer_event(): void
    {
        $sampleFile = $this->sampleDir . 'serialized_label_was_removed_from_organizer.json';
        $this->assertOrganizerLabelEventFixed($sampleFile);
    }

    /**
     * @test
     */
    public function it_knows_the_new_namespace_of_event_was_labelled(): void
    {
        $sampleFile = $this->sampleDir . 'serialized_event_was_labelled_class.json';
        $this->assertClass($sampleFile, LabelAdded::class);
    }

    public function it_replaces_event_id_with_item_id_on_event_was_labelled(): void
    {
        $sampleFile = $this->sampleDir . 'serialized_event_was_labelled_class.json';
        $this->assertEventIdReplacedWithItemId($sampleFile);
    }

    /**
     * @test
     */
    public function it_knows_the_new_namespace_of_event_was_tagged(): void
    {
        $sampleFile = $this->sampleDir . 'serialized_event_was_tagged_class.json';
        $this->assertClass($sampleFile, LabelAdded::class);
    }

    /**
     * @test
     */
    public function it_replaces_event_id_with_item_id_on_event_was_tagged(): void
    {
        $sampleFile = $this->sampleDir . 'serialized_event_was_tagged_class.json';
        $this->assertEventIdReplacedWithItemId($sampleFile);
    }

    /**
     * @test
     */
    public function it_replaces_keyword_with_label_on_event_was_tagged(): void
    {
        $sampleFile = $this->sampleDir . 'serialized_event_was_tagged_class.json';
        $this->assertKeywordReplacedWithLabel($sampleFile);
    }

    /**
     * @test
     */
    public function it_knows_the_new_namespace_of_event_tag_erased(): void
    {
        $sampleFile = $this->sampleDir . 'serialized_event_tag_erased_class.json';
        $this->assertClass($sampleFile, LabelRemoved::class);
    }

    /**
     * @test
     */
    public function it_replaces_event_id_with_item_id_on_event_tag_erased(): void
    {
        $sampleFile = $this->sampleDir . 'serialized_event_tag_erased_class.json';
        $this->assertEventIdReplacedWithItemId($sampleFile);
    }

    /**
     * @test
     */
    public function it_replaces_keyword_with_label_on_event_tag_erased(): void
    {
        $sampleFile = $this->sampleDir . 'serialized_event_tag_erased_class.json';
        $this->assertKeywordReplacedWithLabel($sampleFile);
    }

    /**
     * @test
     */
    public function it_knows_the_new_namespace_of_event_unlabelled(): void
    {
        $sampleFile = $this->sampleDir . 'serialized_event_unlabelled_class.json';
        $this->assertClass($sampleFile, LabelRemoved::class);
    }

    /**
     * @test
     */
    public function it_replaces_event_id_with_item_id_on_event_unlabelled(): void
    {
        $sampleFile = $this->sampleDir . 'serialized_event_unlabelled_class.json';
        $this->assertEventIdReplacedWithItemId($sampleFile);
    }

    /**
     * @test
     */
    public function it_knows_the_new_namespace_of_event_imported_from_udb2_class(): void
    {
        $serialized = SampleFiles::read($this->sampleDir . 'serialized_event_imported_from_udb2_class.json');
        $decoded = Json::decodeAssociatively($serialized);

        $importedFromUDB2 = $this->serializer->deserialize($decoded);

        $this->assertInstanceOf(EventImportedFromUDB2::class, $importedFromUDB2);
    }

    /**
     * @test
     */
    public function it_replaces_event_id_with_item_id_on_event_booking_info_updated(): void
    {
        $sampleFile = $this->sampleDir . 'serialized_event_booking_info_updated_class.json';

        $serialized = SampleFiles::read($sampleFile);
        $decoded = Json::decodeAssociatively($serialized);

        /* @var BookingInfoUpdated $bookingInfoUpdated */
        $bookingInfoUpdated = $this->serializer->deserialize($decoded);

        $this->assertNull($bookingInfoUpdated->getBookingInfo()->getAvailabilityStarts());
        $this->assertNull($bookingInfoUpdated->getBookingInfo()->getAvailabilityEnds());

        $this->assertEventIdReplacedWithItemId($sampleFile);
    }

    /**
     * @test
     */
    public function it_replaces_deprecated_availability_date_formats_on_booking_info_updated(): void
    {
        $sampleFile = $this->sampleDir . 'serialized_event_booking_info_updated_with_deprecated_availability.json';

        $serialized = SampleFiles::read($sampleFile);
        $decoded = Json::decodeAssociatively($serialized);

        /* @var BookingInfoUpdated $bookingInfoUpdated */
        $bookingInfoUpdated = $this->serializer->deserialize($decoded);

        $this->assertEquals(
            DateTimeFactory::fromAtom('2018-02-20T15:11:26+00:00'),
            $bookingInfoUpdated->getBookingInfo()->getAvailabilityStarts()
        );

        $this->assertEquals(
            DateTimeFactory::fromAtom('2018-04-30T14:11:26+00:00'),
            $bookingInfoUpdated->getBookingInfo()->getAvailabilityEnds()
        );
    }

    /**
     * @test
     */
    public function it_replaces_invalid_availability_date_formats_on_booking_info_updated(): void
    {
        $sampleFile = $this->sampleDir . 'serialized_event_booking_info_updated_with_invalid_availability.json';

        $serialized = SampleFiles::read($sampleFile);
        $decoded = Json::decodeAssociatively($serialized);

        /* @var BookingInfoUpdated $bookingInfoUpdated */
        $bookingInfoUpdated = $this->serializer->deserialize($decoded);

        $this->assertNull($bookingInfoUpdated->getBookingInfo()->getAvailabilityStarts());
        $this->assertNull($bookingInfoUpdated->getBookingInfo()->getAvailabilityEnds());
    }

    /**
     * @test
     */
    public function it_replaces_deprecated_url_label_on_booking_info_updated(): void
    {
        $sampleFile = $this->sampleDir . 'serialized_event_booking_info_updated_with_deprecated_url_label.json';

        $serialized = SampleFiles::read($sampleFile);
        $decoded = Json::decodeAssociatively($serialized);

        /* @var BookingInfoUpdated $bookingInfoUpdated */
        $bookingInfoUpdated = $this->serializer->deserialize($decoded);

        $this->assertEquals(
            new MultilingualString(new Language('nl'), 'Reserveer plaatsen'),
            $bookingInfoUpdated->getBookingInfo()->getUrlLabel()
        );
    }

    /**
     * @test
     */
    public function it_keeps_valid_availability_date_formats_on_booking_info_updated(): void
    {
        $sampleFile = $this->sampleDir . 'serialized_event_booking_info_updated_with_valid_availability.json';

        $serialized = SampleFiles::read($sampleFile);
        $decoded = Json::decodeAssociatively($serialized);

        /* @var BookingInfoUpdated $bookingInfoUpdated */
        $bookingInfoUpdated = $this->serializer->deserialize($decoded);

        $this->assertEquals(
            DateTimeFactory::fromAtom('2018-02-20T15:11:26+01:00'),
            $bookingInfoUpdated->getBookingInfo()->getAvailabilityStarts()
        );

        $this->assertEquals(
            DateTimeFactory::fromAtom('2018-04-30T14:11:26+01:00'),
            $bookingInfoUpdated->getBookingInfo()->getAvailabilityEnds()
        );
    }

    /**
     * @test
     */
    public function it_replaces_missing_availability_dates_with_null_on_booking_info_updated(): void
    {
        $sampleFile = $this->sampleDir . 'serialized_event_booking_info_updated_without_availability.json';

        $serialized = SampleFiles::read($sampleFile);
        $decoded = Json::decodeAssociatively($serialized);

        /* @var BookingInfoUpdated $bookingInfoUpdated */
        $bookingInfoUpdated = $this->serializer->deserialize($decoded);

        $this->assertNull($bookingInfoUpdated->getBookingInfo()->getAvailabilityStarts());
        $this->assertNull($bookingInfoUpdated->getBookingInfo()->getAvailabilityEnds());
    }

    /**
     * @test
     * @dataProvider typedIdPlaceEventClassProvider
     */
    public function it_should_replace_place_id_on_older_events_with_item_id(
        string $eventClassFile
    ): void {
        $sampleFile = $this->sampleDir . '/place/' . $eventClassFile;
        $this->assertPlaceIdReplacedWithItemId($sampleFile);
    }

    public function typedIdPlaceEventClassProvider(): array
    {
        return [
            ['booking_info_updated.class.json'],
            ['contact_point_updated.class.json'],
            ['description_updated.class.json'],
            ['organizer_updated.class.json'],
            ['organizer_deleted.class.json'],
            ['typical_age_range_deleted.class.json'],
            ['typical_age_range_updated.class.json'],
        ];
    }

    /**
     * @test
     */
    public function it_replaces_event_id_with_item_id_on_event_typical_age_range_deleted(): void
    {
        $sampleFile = $this->sampleDir . 'serialized_event_typical_age_range_deleted_class.json';
        $this->assertEventIdReplacedWithItemId($sampleFile);
    }

    /**
     * @test
     */
    public function it_replaces_event_id_with_item_id_on_event_typical_age_range_updated(): void
    {
        $sampleFile = $this->sampleDir . 'serialized_event_typical_age_range_updated_class.json';
        $this->assertEventIdReplacedWithItemId($sampleFile);
    }

    /**
     * @test
     */
    public function it_replaces_event_id_with_item_id_on_event_contact_point_updated(): void
    {
        $sampleFile = $this->sampleDir . 'serialized_event_contact_point_updated_class.json';
        $this->assertEventIdReplacedWithItemId($sampleFile);
    }

    /**
     * @test
     */
    public function it_replaces_event_id_with_item_id_on_event_major_info_updated(): void
    {
        $sampleFile = $this->sampleDir . 'serialized_event_major_info_updated_class.json';
        $this->assertEventIdReplacedWithItemId($sampleFile);
    }

    /**
     * @test
     */
    public function it_replaces_event_id_with_item_id_on_event_organizer_updated(): void
    {
        $sampleFile = $this->sampleDir . 'serialized_event_organizer_updated_class.json';
        $this->assertEventIdReplacedWithItemId($sampleFile);
    }

    /**
     * @test
     */
    public function it_replaces_event_id_with_item_id_on__event_organizer_deleted(): void
    {
        $sampleFile = $this->sampleDir . 'serialized_event_organizer_deleted_class.json';
        $this->assertEventIdReplacedWithItemId($sampleFile);
    }

    /**
     * @test
     */
    public function it_replaces_event_id_with_item_id_on_event_description_updated(): void
    {
        $sampleFile = $this->sampleDir . 'serialized_event_description_updated_class.json';
        $this->assertEventIdReplacedWithItemId($sampleFile);
    }

    /**
     * @test
     */
    public function it_replaces_place_id_with_item_id_on_event_facilities_updated(): void
    {
        $sampleFile = $this->sampleDir . 'serialized_event_facilities_updated_class.json';
        $this->assertPlaceIdReplacedWithItemId($sampleFile);
    }

    /**
     * @test
     */
    public function it_replaces_place_id_with_item_id_on_geo_coordinates_updated(): void
    {
        $sampleFile = $this->sampleDir . 'serialized_event_geo_coordinates_updated_class.json';
        $this->assertPlaceIdReplacedWithItemId($sampleFile);
    }

    /**
     * @test
     */
    public function it_should_replace_string_names_with_translatable_objects_in_price_info_updated(): void
    {
        $sampleFile = $this->sampleDir . 'serialized_event_price_info_updated_class.json';
        $serialized = SampleFiles::read($sampleFile);
        $decoded = Json::decodeAssociatively($serialized);

        $expectedPriceInfo = new PriceInfo(
            Tariff::createBasePrice(
                new Money(1500, new Currency('EUR'))
            ),
            new Tariffs(
                new Tariff(
                    new TranslatedTariffName(
                        new Language('nl'),
                        new TariffName('Senioren')
                    ),
                    new Money(1000, new Currency('EUR'))
                ),
                new Tariff(
                    new TranslatedTariffName(
                        new Language('nl'),
                        new TariffName('Studenten')
                    ),
                    new Money(750, new Currency('EUR'))
                )
            )
        );

        /**
         * @var PriceInfoUpdated $event
         */
        $event = $this->serializer->deserialize($decoded);
        $actualPriceInfo = $event->getPriceInfo();

        $this->assertEquals($expectedPriceInfo, $actualPriceInfo);
    }

    /**
     * @test
     */
    public function it_changes_class_for_constraint_created(): void
    {
        $sampleFile = $this->sampleDir . 'serialized_event_constraint_created.json';

        $serialized = SampleFiles::read($sampleFile);
        $decoded = Json::decodeAssociatively($serialized);

        /* @var ConstraintAdded $constraintAdded */
        $constraintAdded = $this->serializer->deserialize($decoded);

        $this->assertInstanceOf(ConstraintAdded::class, $constraintAdded);
    }

    /**
     * @test
     */
    public function it_trims_contact_points(): void
    {
        $sampleFile = $this->sampleDir . 'serialized_event_contact_point_updated_class_with_spaces.json';

        $serialized = SampleFiles::read($sampleFile);
        $decoded = Json::decodeAssociatively($serialized);

        $expectedContactPoint = new ContactPoint(
            new TelephoneNumbers(new TelephoneNumber('0474888888')),
            new EmailAddresses(new EmailAddress('willem@willem.com')),
            new Urls(new Url('http://test.com'))
        );
        /**
         * @var ContactPointUpdated $event
         */
        $event = $this->serializer->deserialize($decoded);

        $actualContactPoint = $event->getContactPoint();

        $this->assertEquals($expectedContactPoint, $actualContactPoint);
    }

    private function assertEventIdReplacedWithItemId(string $sampleFile): void
    {
        $this->assertTypedIdReplacedWithItemId('event', $sampleFile);
    }

    private function assertPlaceIdReplacedWithItemId(string $sampleFile): void
    {
        $this->assertTypedIdReplacedWithItemId('place', $sampleFile);
    }

    private function assertTypedIdReplacedWithItemId(string $type, string $sampleFile): void
    {
        $serialized = SampleFiles::read($sampleFile);
        $decoded = Json::decodeAssociatively($serialized);
        $typedId = $decoded['payload'][$type . '_id'];

        /**
         * @var \CultuurNet\UDB3\Offer\Events\AbstractEvent $abstractEvent
         */
        $abstractEvent = $this->serializer->deserialize($decoded);
        $itemId = $abstractEvent->getItemId();

        $this->assertEquals($typedId, $itemId);
    }

    private function assertKeywordReplacedWithLabel(string $sampleFile): void
    {
        $serialized = SampleFiles::read($sampleFile);
        $decoded = Json::decodeAssociatively($serialized);
        $keyword = $decoded['payload']['keyword'];

        /** @var AbstractLabelEvent $abstractLabelEvent */
        $abstractLabelEvent = $this->serializer->deserialize($decoded);
        $labelName = $abstractLabelEvent->getLabelName();

        $this->assertEquals($keyword, $labelName);
    }

    private function assertClass(string $sampleFile, string $expectedClass): void
    {
        $serialized = SampleFiles::read($sampleFile);
        $decoded = Json::decodeAssociatively($serialized);

        $newEvent = $this->serializer->deserialize($decoded);

        $this->assertInstanceOf($expectedClass, $newEvent);
    }

    private function assertLabelNameAdded(string $sampleFile): void
    {
        $serialized = SampleFiles::read($sampleFile);
        $decoded = Json::decodeAssociatively($serialized);

        /** @var AbstractEvent $labelEvent */
        $labelEvent = $this->serializer->deserialize($decoded);

        $this->assertEquals('2dotstwice', $labelEvent->getName());
    }

    private function assertOrganizerLabelEventFixed(string $sampleFile): void
    {
        $serialized = SampleFiles::read($sampleFile);
        $decoded = Json::decodeAssociatively($serialized);

        /** @var LabelEventInterface $labelEvent */
        $labelEvent = $this->serializer->deserialize($decoded);

        $this->assertEquals('2dotstwice', $labelEvent->getLabelName());
        $this->assertFalse($labelEvent->isLabelVisible());
    }

    /**
     * @test
     * @dataProvider emptyDescriptionsDataProvider
     * @param AbstractDescriptionUpdated|AbstractDescriptionTranslated $expectedEvent
     */
    public function it_fills_empty_descriptions($expectedEvent, string $serialized): void
    {
        $decoded = Json::decodeAssociatively($serialized);

        $event = $this->serializer->deserialize($decoded);

        $this->assertEquals($expectedEvent->getDescription(), $event->getDescription());
    }

    public static function emptyDescriptionsDataProvider(): array
    {
        return [
            'Fill empty event description' => [
                new EventDescriptionUpdated(
                    '9c6145c5-4a53-4c36-b51b-3ccef8a1507c',
                    new Description('---')
                ),
                self::createDescriptionUpdatedPayload(
                    EventDescriptionUpdated::class,
                    ''
                ),
            ],
            'Fill empty event description translated' => [
                new EventDescriptionTranslated(
                    '9c6145c5-4a53-4c36-b51b-3ccef8a1507c',
                    new Language('nl'),
                    new Description('---')
                ),
                self::createDescriptionTranslatedPayload(
                    EventDescriptionTranslated::class,
                    ''
                ),
            ],
            'Do not fill event with existing description' => [
                new EventDescriptionUpdated(
                    '9c6145c5-4a53-4c36-b51b-3ccef8a1507c',
                    new Description('Lorum ipsum')
                ),
                self::createDescriptionUpdatedPayload(
                    EventDescriptionUpdated::class,
                    'Lorum ipsum'
                ),
            ],
            'Fill empty place description' => [
                new PlaceDescriptionUpdated(
                    '9c6145c5-4a53-4c36-b51b-3ccef8a1507c',
                    new Description('---')
                ),
                self::createDescriptionUpdatedPayload(
                    PlaceDescriptionUpdated::class,
                    ''
                ),
            ],
            'Fill empty place description translated' => [
                new PlaceDescriptionTranslated(
                    '9c6145c5-4a53-4c36-b51b-3ccef8a1507c',
                    new Language('nl'),
                    new Description('---')
                ),
                self::createDescriptionTranslatedPayload(
                    PlaceDescriptionTranslated::class,
                    ''
                ),
            ],
            'Do not fill place with existing description' => [
                new PlaceDescriptionUpdated(
                    '9c6145c5-4a53-4c36-b51b-3ccef8a1507c',
                    new Description('Lorum ipsum')
                ),
                self::createDescriptionUpdatedPayload(
                    PlaceDescriptionUpdated::class,
                    'Lorum ipsum'
                ),
            ],
            'Take into account spaces in the description' => [
                new PlaceDescriptionUpdated(
                    '9c6145c5-4a53-4c36-b51b-3ccef8a1507c',
                    new Description('---')
                ),
                self::createDescriptionUpdatedPayload(
                    PlaceDescriptionUpdated::class,
                    ' '
                ),
            ],
        ];
    }

    private static function createDescriptionUpdatedPayload(string $class, string $description): string
    {
        return Json::encode([
            'class' => $class,
            'payload' => [
                'item_id' => '9c6145c5-4a53-4c36-b51b-3ccef8a1507c',
                'description' => $description,
            ],
        ]);
    }

    private static function createDescriptionTranslatedPayload(string $class, string $description): string
    {
        return Json::encode([
            'class' => $class,
            'payload' => [
                'item_id' => '9c6145c5-4a53-4c36-b51b-3ccef8a1507c',
                'language' => 'nl',
                'description' => $description,
            ],
        ]);
    }
}
