<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Events;

use CultuurNet\UDB3\Model\ValueObject\Geography\Address;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Locality;
use CultuurNet\UDB3\Model\ValueObject\Geography\PostalCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Street;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use PHPUnit\Framework\TestCase;

class AddressTranslatedTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_be_serializable_and_deserializable(): void
    {
        $event = new AddressTranslated(
            'a9088117-5ec8-4117-8ce0-5ce27e685055',
            new Address(
                new Street('Eenmeilaan 35'),
                new PostalCode('3010'),
                new Locality('Kessel-Lo'),
                new CountryCode('BE')
            ),
            new Language('en')
        );

        $eventAsArray = [
            'place_id' => 'a9088117-5ec8-4117-8ce0-5ce27e685055',
            'address' => [
                'streetAddress' => 'Eenmeilaan 35',
                'postalCode' => '3010',
                'addressLocality' => 'Kessel-Lo',
                'addressCountry' => 'BE',
            ],
            'language' => 'en',
        ];

        $serializedEvent = $event->serialize();
        $this->assertEquals($eventAsArray, $serializedEvent);

        $deserializedEvent = AddressTranslated::deserialize($eventAsArray);
        $this->assertEquals($event, $deserializedEvent);
    }
}
