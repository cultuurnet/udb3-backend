<?php

namespace CultuurNet\UDB3\Organizer\Events;

use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\Language;
use PHPUnit\Framework\TestCase;
use ValueObjects\Geography\Country;

class AddressTranslatedTest extends TestCase
{
    /**
     * @var AddressTranslated
     */
    private $addressTranslated;

    protected function setUp(): void
    {
        $this->addressTranslated = new AddressTranslated(
            '0460ffbd-1c85-4bad-9a8f-be1f981648e7',
            new Address(
                new Street('Martelarenplein 12'),
                new PostalCode('3000'),
                new Locality('Leuven'),
                Country::fromNative('BE')
            ),
            new Language('nl')
        );
    }

    /**
     * @test
     */
    public function it_stores_a_language(): void
    {
        $this->assertEquals(
            new Language('nl'),
            $this->addressTranslated->getLanguage()
        );
    }

    /**
     * @test
     */
    public function it_can_be_serialized_and_deserialized(): void
    {
        $serializedAddress = $this->addressTranslated->serialize();
        $actualAddressTranslated = AddressTranslated::deserialize($serializedAddress);

        $this->assertEquals($this->addressTranslated, $actualAddressTranslated);
    }
}
