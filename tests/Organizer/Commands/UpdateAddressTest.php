<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Commands;

use CultuurNet\UDB3\Model\ValueObject\Geography\Address;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Locality;
use CultuurNet\UDB3\Model\ValueObject\Geography\PostalCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Street;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use PHPUnit\Framework\TestCase;

final class UpdateAddressTest extends TestCase
{
    private string $organizerId;

    private Address $address;

    private Language $language;

    private UpdateAddress $updateAddress;

    protected function setUp(): void
    {
        $this->organizerId = '9b465926-dbbc-4170-aa9b-0babaa6af5f5';

        $this->address = new Address(
            new Street('Martelarenplein 1'),
            new PostalCode('3000'),
            new Locality('Leuven'),
            new CountryCode('BE')
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
    public function it_stores_an_organizer_id(): void
    {
        $this->assertEquals(
            $this->organizerId,
            $this->updateAddress->getItemId()
        );
    }

    /**
     * @test
     */
    public function it_stores_an_address(): void
    {
        $this->assertEquals(
            $this->address,
            $this->updateAddress->getAddress()
        );
    }

    /**
     * @test
     */
    public function it_stores_a_language(): void
    {
        $this->assertEquals(
            $this->language,
            $this->updateAddress->getLanguage()
        );
    }
}
