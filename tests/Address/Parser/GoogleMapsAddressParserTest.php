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
use Psr\Log\LoggerInterface;

class GoogleMapsAddressParserTest extends TestCase
{
    /**
     * @var Geocoder&MockObject
     */
    private $geocoder;

    private GoogleMapsAddressParser $parser;

    /**
     * @var LoggerInterface&MockObject
     */
    private $logger;

    protected function setUp(): void
    {
        $this->geocoder = $this->createMock(Geocoder::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->parser = new GoogleMapsAddressParser($this->geocoder);
        $this->parser->setLogger($this->logger);

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

    /**
     * @test
     */
    public function it_geocodes_an_address_with_missing_number_and_returns_a_parsed_address(): void
    {
        $this->geocoder->expects($this->once())
            ->method('geocode')
            ->with('Martelarenplein, 3000 Leuven, BE')
            ->willReturn(
                new AddressCollection([
                    new Address(
                        'Google',
                        new AdminLevelCollection(),
                        null,
                        null,
                        null,
                        'Martelarenplein',
                        '3000',
                        'Leuven',
                        null,
                        new Country('BE')
                    ),
                ])
            );

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('No street number found for address: "Martelarenplein, 3000 Leuven, BE"');

        $parsedAddress = $this->parser->parse('Martelarenplein, 3000 Leuven, BE');

        $this->assertEquals(
            new ParsedAddress(
                'Martelarenplein',
                null,
                '3000',
                'Leuven'
            ),
            $parsedAddress
        );
    }

    /**
     * @test
     */
    public function it_handles_a_geocoder_exception(): void
    {
        $this->geocoder->expects($this->once())
            ->method('geocode')
            ->with('Martelarenplein 1, 3000 Leuven, BE')
            ->willThrowException(new CollectionIsEmpty('Collection is empty.'));

        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                'No results for address: "Martelarenplein 1, 3000 Leuven, BE". Exception message: Collection is empty.'
            );

        $parsedAddress = $this->parser->parse('Martelarenplein 1, 3000 Leuven, BE');

        $this->assertNull($parsedAddress);
    }
}
