<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UDB2\Event;

use CultuurNet\UDB3\UDB2\XML\XMLValidationError;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;

class EventXMLValidatorServiceTest extends TestCase
{
    /**
     * @test
     */
    public function it_throws_for_same_location_and_organizer_cdbid()
    {
        $eventXmlValidatorService = new EventXMLValidatorService(
            new StringLiteral('http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL')
        );

        $eventXml = file_get_contents(__DIR__ . '/samples/event_same_cdbid_location_and_organizer.xml');

        // @codingStandardsIgnoreStart
        $expected = [
            new XMLValidationError(
                'The event with cdbid d9b7d2b9-2dd4-48d7-905d-803e679b6378, has a location and place with the same cdbid f2be2e5c-715c-4e83-9c9b-c8bb9133003b',
                0,
                0
            ),
        ];
        // @codingStandardsIgnoreEnd

        $actual = $eventXmlValidatorService->validate($eventXml);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function does_not_throw_for_location_and_organizer_with_null_cdbid()
    {
        $eventXmlValidatorService = new EventXMLValidatorService(
            new StringLiteral('http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL')
        );

        $eventXml = file_get_contents(__DIR__ . '/samples/event_with_null_value_for_cdbid_location_and_organizer.xml');

        $expected = [];

        $actual = $eventXmlValidatorService->validate($eventXml);

        $this->assertEquals($expected, $actual);
    }
}
