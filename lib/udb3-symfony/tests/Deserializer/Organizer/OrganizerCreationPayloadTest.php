<?php

namespace CultuurNet\UDB3\Symfony\Deserializer\Organizer;

use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\ContactPoint;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Title;
use PHPUnit\Framework\TestCase;
use ValueObjects\Geography\Country;
use ValueObjects\Web\Url;

class OrganizerCreationPayloadTest extends TestCase
{
    /**
     * @var Language
     */
    private $mainLanguage;

    /**
     * @var Url
     */
    private $website;

    /**
     * @var Title
     */
    private $title;

    /**
     * @var Address
     */
    private $address;

    /**
     * @var ContactPoint
     */
    private $contactPoint;

    /**
     * @var OrganizerCreationPayload
     */
    private $organizerCreationPayload;

    protected function setUp()
    {
        $this->mainLanguage = new Language('en');

        $this->website = Url::fromNative('http://www.domain.be');

        $this->title = new Title('Het Depot');

        $this->address = new Address(
            new Street('Martelarenplein 101'),
            new PostalCode('3000'),
            new Locality('Leuven'),
            Country::fromNative('BE')
        );

        $this->contactPoint = new ContactPoint(
            [],
            ['jimi.hendrix@depot.be'],
            ['www.experience.be']
        );

        $this->organizerCreationPayload = new OrganizerCreationPayload(
            $this->mainLanguage,
            $this->website,
            $this->title,
            $this->address,
            $this->contactPoint
        );
    }

    /**
     * @test
     */
    public function it_stores_a_main_language()
    {
        $this->assertEquals(
            $this->mainLanguage,
            $this->organizerCreationPayload->getMainLanguage()
        );
    }

    /**
     * @test
     */
    public function it_stores_a_website()
    {
        $this->assertEquals(
            $this->website,
            $this->organizerCreationPayload->getWebsite()
        );
    }

    /**
     * @test
     */
    public function it_stores_a_title()
    {
        $this->assertEquals(
            $this->title,
            $this->organizerCreationPayload->getTitle()
        );
    }

    /**
     * @test
     */
    public function it_stores_an_address()
    {
        $this->assertEquals(
            $this->address,
            $this->organizerCreationPayload->getAddress()
        );
    }

    /**
     * @test
     */
    public function it_stores_a_contact_point()
    {
        $this->assertEquals(
            $this->contactPoint,
            $this->organizerCreationPayload->getContactPoint()
        );
    }
}
