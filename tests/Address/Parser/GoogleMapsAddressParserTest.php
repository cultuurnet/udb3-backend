<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Address\Parser;

use Geocoder\Exception\CollectionIsEmpty;
use Geocoder\Geocoder;
use Geocoder\Model\Address;
use Geocoder\Model\AddressCollection;
use Geocoder\Model\AdminLevelCollection;
use Geocoder\Model\Country;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GoogleMapsAddressParserTest extends TestCase
{
    /**
     * @var Geocoder|MockObject
     */
    private $geocoder;

    private GoogleMapsAddressParser $parser;

    protected function setUp(): void
    {
        $this->geocoder = $this->createMock(Geocoder::class);
        $this->parser = new GoogleMapsAddressParser($this->geocoder);

        parent::setUp();
    }

    /**
     * @test
     */
    public function it_geocodes_an_address_and_returns_a_parsed_address(): void
    {
        $this->geocoder->expects($this->once())
            ->method('geocode')
            ->with('Martelarenplein 1, 3000 Leuven, BE')
            ->willReturn(
                new AddressCollection([
                    new Address(
                        'Google',
                        new AdminLevelCollection(),
                        null,
                        null,
                        '1',
                        'Martelarenplein',
                        '3000',
                        'Leuven',
                        null,
                        new Country('BE')
                    ),
                ])
            );

        $parsedAddress = $this->parser->parse('Martelarenplein 1, 3000 Leuven, BE');

        $this->assertEquals(
            new ParsedAddress(
                'Martelarenplein',
                '1',
                '3000',
                'Leuven'
            ),
            $parsedAddress
        );
    }

    public function it_handles_a_geocoder_exception(): void
    {
        $this->geocoder->expects($this->once())
            ->method('geocode')
            ->with('Martelarenplein 1, 3000 Leuven, BE')
            ->willThrowException(new CollectionIsEmpty());

        $parsedAddress = $this->parser->parse('Martelarenplein 1, 3000 Leuven, BE');

        $this->assertNull($parsedAddress);
    }
}
