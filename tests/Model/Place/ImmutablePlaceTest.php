<?php

namespace CultuurNet\UDB3\Model\Place;

use CultuurNet\UDB3\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Geocoding\Coordinate\Latitude;
use CultuurNet\UDB3\Geocoding\Coordinate\Longitude;
use CultuurNet\UDB3\Model\ValueObject\Calendar\CalendarWithOpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\DateRange;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\PermanentCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SingleSubEventCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Status;
use CultuurNet\UDB3\Model\ValueObject\Calendar\StatusType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvent;
use CultuurNet\UDB3\Model\ValueObject\Contact\BookingInfo;
use CultuurNet\UDB3\Model\ValueObject\Contact\ContactPoint;
use CultuurNet\UDB3\Model\ValueObject\Geography\Address;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Locality;
use CultuurNet\UDB3\Model\ValueObject\Geography\PostalCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Street;
use CultuurNet\UDB3\Model\ValueObject\Geography\TranslatedAddress;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Moderation\WorkflowStatus;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Categories;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use CultuurNet\UDB3\Model\ValueObject\Text\TranslatedTitle;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use PHPUnit\Framework\TestCase;

class ImmutablePlaceTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_throw_an_exception_if_the_list_of_categories_is_empty_and_it_is_not_a_dummy_location()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Categories should not be empty (eventtype required).');

        new ImmutablePlace(
            $this->getId(),
            $this->getMainLanguage(),
            $this->getTitle(),
            $this->getCalendar(),
            $this->getAddress(),
            new Categories()
        );
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_an_unsupported_calendar_is_injected()
    {
        $calendar = new SingleSubEventCalendar(
            new SubEvent(
                new DateRange(
                    \DateTimeImmutable::createFromFormat('d/m/Y', '10/01/2018'),
                    \DateTimeImmutable::createFromFormat('d/m/Y', '11/01/2018')
                ),
                new Status(StatusType::Available())
            )
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Given calendar should have opening hours.');

        new ImmutablePlace(
            $this->getId(),
            $this->getMainLanguage(),
            $this->getTitle(),
            $calendar,
            $this->getAddress(),
            $this->getTerms()
        );
    }

    /**
     * @test
     */
    public function it_should_return_the_injected_address()
    {
        $address = $this->getAddress();
        $place = $this->getPlace();

        $this->assertEquals($address, $place->getAddress());
    }

    /**
     * @test
     */
    public function it_should_return_a_copy_with_an_updated_address()
    {
        $address = $this->getAddress();
        $updatedAddress = $address->withTranslation(
            new Language('fr'),
            new Address(
                new Street('Quai du Hainaut 41-43'),
                new PostalCode('1080'),
                new Locality('Bruxelles'),
                new CountryCode('BE')
            )
        );

        $place = $this->getPlace();
        $updatedPlace = $place->withAddress($updatedAddress);

        $this->assertNotEquals($place, $updatedPlace);
        $this->assertEquals($address, $place->getAddress());
        $this->assertEquals($updatedAddress, $updatedPlace->getAddress());
    }

    /**
     * @test
     */
    public function it_should_return_no_coordinates_by_default()
    {
        $this->assertNull($this->getPlace()->getGeoCoordinates());
    }

    /**
     * @test
     */
    public function it_should_return_a_copy_with_updated_coordinates()
    {
        $coordinates = new Coordinates(
            new Latitude(45.123),
            new Longitude(132.456)
        );

        $place = $this->getPlace();
        $updatedPlace = $place->withGeoCoordinates($coordinates);

        $this->assertNull($place->getGeoCoordinates());
        $this->assertEquals($coordinates, $updatedPlace->getGeoCoordinates());
    }

    /**
     * @test
     */
    public function it_should_return_a_copy_without_coordinates()
    {
        $coordinates = new Coordinates(
            new Latitude(45.123),
            new Longitude(132.456)
        );

        $place = $this->getPlace()->withGeoCoordinates($coordinates);
        $updatedPlace = $place->withoutGeoCoordinates();

        $this->assertEquals($coordinates, $place->getGeoCoordinates());
        $this->assertNull($updatedPlace->getGeoCoordinates());
    }

    /**
     * @test
     */
    public function it_should_not_be_a_dummy_location_by_default()
    {
        $this->assertFalse($this->getPlace()->isDummyLocation());
    }

    /**
     * @test
     */
    public function it_should_be_able_to_create_dummy_locations()
    {
        $dummy = ImmutablePlace::createDummyLocation(
            $this->getMainLanguage(),
            $this->getTitle(),
            $this->getAddress()
        );

        $this->assertInstanceOf(Place::class, $dummy);
        $this->assertTrue($dummy->isDummyLocation());

        $this->assertEquals(ImmutablePlace::getDummyLocationId(), $dummy->getId());
        $this->assertEquals($this->getMainLanguage(), $dummy->getMainLanguage());
        $this->assertEquals($this->getTitle(), $dummy->getTitle());
        $this->assertNull($dummy->getDescription());
        $this->assertEquals(new Categories(), $dummy->getTerms());
        $this->assertEquals(new Labels(), $dummy->getLabels());
        $this->assertNull($dummy->getAgeRange());
        $this->assertNull($dummy->getPriceInfo());
        $this->assertEquals(new BookingInfo(), $dummy->getBookingInfo());
        $this->assertEquals(new ContactPoint(), $dummy->getContactPoint());
        $this->assertEquals(WorkflowStatus::DRAFT(), $dummy->getWorkflowStatus());
        $this->assertEquals(new PermanentCalendar(new OpeningHours()), $dummy->getCalendar());
        $this->assertEquals($this->getAddress(), $dummy->getAddress());
        $this->assertNull($dummy->getGeoCoordinates());
    }

    /**
     * @return UUID
     */
    private function getId()
    {
        return new UUID('aadcee95-6180-4924-a8eb-ed829d4957a2');
    }

    /**
     * @return Language
     */
    private function getMainLanguage()
    {
        return new Language('nl');
    }

    /**
     * @return TranslatedTitle
     */
    private function getTitle()
    {
        return new TranslatedTitle(
            $this->getMainLanguage(),
            new Title('foo')
        );
    }

    /**
     * @return CalendarWithOpeningHours
     */
    private function getCalendar()
    {
        return new PermanentCalendar(new OpeningHours());
    }

    /**
     * @return TranslatedAddress
     */
    private function getAddress()
    {
        $address = new Address(
            new Street('Henegouwenkaai 41-43'),
            new PostalCode('1080'),
            new Locality('Brussel'),
            new CountryCode('BE')
        );

        return new TranslatedAddress(new Language('nl'), $address);
    }

    /**
     * @return Categories
     */
    private function getTerms()
    {
        return new Categories(
            new Category(
                new CategoryID('0.50.1.0.0'),
                new CategoryLabel('concert'),
                new CategoryDomain('eventtype')
            )
        );
    }

    /**
     * @return ImmutablePlace
     */
    private function getPlace()
    {
        return new ImmutablePlace(
            $this->getId(),
            $this->getMainLanguage(),
            $this->getTitle(),
            $this->getCalendar(),
            $this->getAddress(),
            $this->getTerms()
        );
    }
}
