<?php

namespace CultuurNet\UDB3\Organizer\Commands;

use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\Language;
use PHPUnit\Framework\TestCase;
use ValueObjects\Geography\Country;

class UpdateAddressTest extends TestCase
{
    /**
     * @var string
     */
    private $organizerId;

    /**
     * @var Address
     */
    private $address;

    /**
     * @var Language
     */
    private $language;

    /**
     * @var UpdateAddress
     */
    private $updateAddress;

    protected function setUp()
    {
        $this->organizerId = '9b465926-dbbc-4170-aa9b-0babaa6af5f5';

        $this->address = new Address(
            new Street('Martelarenplein 1'),
            new PostalCode('3000'),
            new Locality('Leuven'),
            Country::fromNative('BE')
        );

        $this->language = new Language('nl');

        $this->updateAddress = new UpdateAddress(
            $this->organizerId,
            $this->address,
            $this->language
        );
    }

    /**
     * @test
     */
    public function it_stores_an_organizer_id()
    {
        $this->assertEquals(
            $this->organizerId,
            $this->updateAddress->getOrganizerId()
        );
    }

    /**
     * @test
     */
    public function it_stores_an_address()
    {
        $this->assertEquals(
            $this->address,
            $this->updateAddress->getAddress()
        );
    }

    /**
     * @test
     */
    public function it_stores_a_language()
    {
        $this->assertEquals(
            $this->language,
            $this->updateAddress->getLanguage()
        );
    }
}
