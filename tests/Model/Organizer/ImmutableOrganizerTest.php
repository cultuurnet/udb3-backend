<?php

namespace CultuurNet\UDB3\Model\Organizer;

use CultuurNet\Geocoding\Coordinate\Coordinates;
use CultuurNet\Geocoding\Coordinate\Latitude;
use CultuurNet\Geocoding\Coordinate\Longitude;
use CultuurNet\UDB3\Model\ValueObject\Contact\ContactPoint;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumber;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumbers;
use CultuurNet\UDB3\Model\ValueObject\Geography\Address;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Locality;
use CultuurNet\UDB3\Model\ValueObject\Geography\PostalCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Street;
use CultuurNet\UDB3\Model\ValueObject\Geography\TranslatedAddress;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use CultuurNet\UDB3\Model\ValueObject\Text\TranslatedTitle;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use PHPUnit\Framework\TestCase;

class ImmutableOrganizerTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_return_the_constructor_properties()
    {
        $organizer = $this->getOrganizer();

        $this->assertEquals($this->getId(), $organizer->getId());
        $this->assertEquals($this->getMainLanguage(), $organizer->getMainLanguage());
        $this->assertEquals($this->getTitle(), $organizer->getName());
        $this->assertEquals($this->getTitle(), $organizer->getName());
    }

    /**
     * @test
     */
    public function it_has_optional_url_property()
    {
        $organizer = new ImmutableOrganizer(
            $this->getId(),
            $this->getMainLanguage(),
            $this->getTitle()
        );

        $this->assertEquals($this->getId(), $organizer->getId());
        $this->assertEquals($this->getMainLanguage(), $organizer->getMainLanguage());
        $this->assertEquals($this->getTitle(), $organizer->getName());
        $this->assertNull($organizer->getUrl());
    }

    /**
     * @test
     */
    public function it_should_return_a_copy_with_an_updated_name()
    {
        $name = $this->getTitle();
        $updatedName = $name->withTranslation(
            new Language('fr'),
            new Title('Publiq FR')
        );

        $organizer = $this->getOrganizer();
        $updatedOrganizer = $organizer->withName($updatedName);

        $this->assertNotEquals($organizer, $updatedOrganizer);
        $this->assertEquals($name, $organizer->getName());
        $this->assertEquals($updatedName, $updatedOrganizer->getName());
    }

    /**
     * @test
     */
    public function it_should_return_a_copy_with_an_updated_url()
    {
        $url = $this->getUrl();
        $updatedUrl = new Url('https://www.google.com');

        $organizer = $this->getOrganizer();
        $updatedOrganizer = $organizer->withUrl($updatedUrl);

        $this->assertNotEquals($organizer, $updatedOrganizer);
        $this->assertEquals($url, $organizer->getUrl());
        $this->assertEquals($updatedUrl, $updatedOrganizer->getUrl());
    }

    /**
     * @test
     */
    public function it_should_return_no_address_by_default()
    {
        $this->assertNull($this->getOrganizer()->getAddress());
    }

    /**
     * @test
     */
    public function it_should_return_a_copy_with_an_updated_address()
    {
        $address = new TranslatedAddress(
            new Language('nl'),
            new Address(
                new Street('Henegouwenkaai 41-43'),
                new PostalCode('1080'),
                new Locality('Brussel'),
                new CountryCode('BE')
            )
        );

        $organizer = $this->getOrganizer();
        $updatedOrganizer = $organizer->withAddress($address);

        $this->assertNotEquals($organizer, $updatedOrganizer);
        $this->assertNull($organizer->getAddress());
        $this->assertEquals($address, $updatedOrganizer->getAddress());
    }

    /**
     * @test
     */
    public function it_should_return_a_copy_without_an_address()
    {
        $address = new TranslatedAddress(
            new Language('nl'),
            new Address(
                new Street('Henegouwenkaai 41-43'),
                new PostalCode('1080'),
                new Locality('Brussel'),
                new CountryCode('BE')
            )
        );

        $organizer = $this->getOrganizer()->withAddress($address);
        $updatedOrganizer = $organizer->withoutAddress();

        $this->assertNotEquals($organizer, $updatedOrganizer);
        $this->assertEquals($address, $organizer->getAddress());
        $this->assertNull($updatedOrganizer->getAddress());
    }

    /**
     * @test
     */
    public function it_should_return_no_coordinates_by_default()
    {
        $this->assertNull($this->getOrganizer()->getGeoCoordinates());
    }

    /**
     * @test
     */
    public function it_should_return_a_copy_with_updated_coordinates()
    {
        $coordinates = new Coordinates(
            new Latitude(50.8793916),
            new Longitude(4.7019674)
        );

        $organizer = $this->getOrganizer();
        $updatedOrganizer = $organizer->withGeoCoordinates($coordinates);

        $this->assertNotEquals($organizer, $updatedOrganizer);
        $this->assertNull($organizer->getGeoCoordinates());
        $this->assertEquals($coordinates, $updatedOrganizer->getGeoCoordinates());
    }

    /**
     * @test
     */
    public function it_should_return_a_copy_without_coordinates()
    {
        $coordinates = new Coordinates(
            new Latitude(50.8793916),
            new Longitude(4.7019674)
        );

        $organizer = $this->getOrganizer()->withGeoCoordinates($coordinates);
        $updatedOrganizer = $organizer->withoutGeoCoordinates();

        $this->assertNotEquals($organizer, $updatedOrganizer);
        $this->assertEquals($coordinates, $organizer->getGeoCoordinates());
        $this->assertNull($updatedOrganizer->getGeoCoordinates());
    }

    /**
     * @test
     */
    public function it_should_return_an_empty_list_of_labels_by_default()
    {
        $this->assertEquals(new Labels(), $this->getOrganizer()->getLabels());
    }

    /**
     * @test
     */
    public function it_should_return_a_copy_with_updated_labels()
    {
        $labels = new Labels(
            new Label(new LabelName('foo'), true),
            new Label(new LabelName('bar'), false)
        );

        $organizer = $this->getOrganizer();
        $updatedOrganizer = $organizer->withLabels($labels);

        $this->assertNotEquals($organizer, $updatedOrganizer);
        $this->assertEquals(new Labels(), $organizer->getLabels());
        $this->assertEquals($labels, $updatedOrganizer->getLabels());
    }

    /**
     * @test
     */
    public function it_should_return_an_empty_contact_point_by_default()
    {
        $this->assertEquals(new ContactPoint(), $this->getOrganizer()->getContactPoint());
    }

    /**
     * @test
     */
    public function it_should_return_a_copy_with_an_updated_contact_point()
    {
        $contactPoint = new ContactPoint();
        $updatedContactPoint = $contactPoint->withTelephoneNumbers(
            new TelephoneNumbers(
                new TelephoneNumber('02 551 18 70')
            )
        );

        $organizer = $this->getOrganizer();
        $updatedOrganizer = $organizer->withContactPoint($updatedContactPoint);

        $this->assertNotEquals($organizer, $updatedOrganizer);
        $this->assertEquals($contactPoint, $organizer->getContactPoint());
        $this->assertEquals($updatedContactPoint, $updatedOrganizer->getContactPoint());
    }

    /**
     * @return UUID
     */
    private function getId()
    {
        return new UUID('6db73fca-a23b-4c48-937d-62aaea73fbe8');
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
        return new TranslatedTitle($this->getMainLanguage(), new Title('Publiq'));
    }

    /**
     * @return Url
     */
    private function getUrl()
    {
        return new Url('https://www.publiq.be');
    }

    /**
     * @return ImmutableOrganizer
     */
    private function getOrganizer()
    {
        return new ImmutableOrganizer(
            $this->getId(),
            $this->getMainLanguage(),
            $this->getTitle(),
            $this->getUrl()
        );
    }
}
