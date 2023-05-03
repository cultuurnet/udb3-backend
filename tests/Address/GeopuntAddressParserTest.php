<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Address;

use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\TestLogger;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;

class GeopuntAddressParserTest extends TestCase
{
    /** @var ClientInterface&MockObject  */
    private ClientInterface $httpClient;
    private GeopuntAddressParser $geopuntAddressParser;
    private TestLogger $logger;
    private TestLogger $expectedLogs;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(ClientInterface::class);
        $this->geopuntAddressParser = new GeopuntAddressParser($this->httpClient);

        $this->logger = new TestLogger();
        $this->geopuntAddressParser->setLogger($this->logger);

        $this->expectedLogs = new TestLogger();
    }

    /**
     * @test
     */
    public function it_looks_up_the_given_address_in_the_geopunt_api_and_returns_a_parsed_address(): void
    {
        $address = 'Martelarenplein 1, 3000 Leuven, BE';

        $expectedRequest = new Request(
            'GET',
            'https://loc.geopunt.be/v4/Location?q=Martelarenplein%201,%203000%20Leuven,%20BE'
        );

        $mockResponseData = [
            'LocationResult' => [
                [
                    'Municipality' => 'Leuven',
                    'Zipcode' => '3000',
                    'Thoroughfarename' => 'Martelarenplein',
                    'Housenumber' => '12',
                ],
            ],
        ];
        $mockResponse = new Response(200, [], Json::encode($mockResponseData));

        $this->httpClient->expects($this->once())
            ->method('sendRequest')
            ->with($expectedRequest)
            ->willReturn($mockResponse);

        $expectedParsedAddress = new ParsedAddress(
            'Martelarenplein',
            '12',
            '3000',
            'Leuven'
        );

        $this->expectedLogs->info('GET https://loc.geopunt.be/v4/Location?q=Martelarenplein%201,%203000%20Leuven,%20BE responded with status code 200 and body {"LocationResult":[{"Municipality":"Leuven","Zipcode":"3000","Thoroughfarename":"Martelarenplein","Housenumber":"12"}]}');
        $this->expectedLogs->info('Successfully parsed response body.');

        $actualParsedAddress = $this->geopuntAddressParser->parse($address);

        $this->assertEquals($expectedParsedAddress, $actualParsedAddress);
        $this->assertLogs();
    }

    /**
     * @test
     */
    public function it_can_handle_housenumber_being_null(): void
    {
        $address = 'Martelarenplein 1, 3000 Leuven, BE';

        $expectedRequest = new Request(
            'GET',
            'https://loc.geopunt.be/v4/Location?q=Martelarenplein%201,%203000%20Leuven,%20BE'
        );

        $mockResponseData = [
            'LocationResult' => [
                [
                    'Municipality' => 'Leuven',
                    'Zipcode' => '3000',
                    'Thoroughfarename' => 'Martelarenplein',
                    'Housenumber' => null,
                ],
            ],
        ];
        $mockResponse = new Response(200, [], Json::encode($mockResponseData));

        $this->httpClient->expects($this->once())
            ->method('sendRequest')
            ->with($expectedRequest)
            ->willReturn($mockResponse);

        $expectedParsedAddress = new ParsedAddress(
            'Martelarenplein',
            null,
            '3000',
            'Leuven'
        );

        $this->expectedLogs->info('GET https://loc.geopunt.be/v4/Location?q=Martelarenplein%201,%203000%20Leuven,%20BE responded with status code 200 and body {"LocationResult":[{"Municipality":"Leuven","Zipcode":"3000","Thoroughfarename":"Martelarenplein","Housenumber":null}]}');
        $this->expectedLogs->info('Successfully parsed response body.');

        $actualParsedAddress = $this->geopuntAddressParser->parse($address);

        $this->assertEquals($expectedParsedAddress, $actualParsedAddress);
        $this->assertLogs();
    }

    /**
     * @test
     */
    public function it_can_handle_json_syntax_errors(): void
    {
        $address = 'Martelarenplein 1, 3000 Leuven, BE';

        $expectedRequest = new Request(
            'GET',
            'https://loc.geopunt.be/v4/Location?q=Martelarenplein%201,%203000%20Leuven,%20BE'
        );

        $mockResponse = new Response(200, [], '{{}');

        $this->httpClient->expects($this->once())
            ->method('sendRequest')
            ->with($expectedRequest)
            ->willReturn($mockResponse);

        $this->expectedLogs->info('GET https://loc.geopunt.be/v4/Location?q=Martelarenplein%201,%203000%20Leuven,%20BE responded with status code 200 and body {{}');
        $this->expectedLogs->error('Caught \JsonException while decoding response body.', ['message' => 'Syntax error']);

        $actualParsedAddress = $this->geopuntAddressParser->parse($address);

        $this->assertNull($actualParsedAddress);
        $this->assertLogs();
    }

    /**
     * @test
     */
    public function it_can_handle_missing_LocationResult_property(): void
    {
        $address = 'Martelarenplein 1, 3000 Leuven, BE';

        $expectedRequest = new Request(
            'GET',
            'https://loc.geopunt.be/v4/Location?q=Martelarenplein%201,%203000%20Leuven,%20BE'
        );

        $mockResponse = new Response(200, [], '{}');

        $this->httpClient->expects($this->once())
            ->method('sendRequest')
            ->with($expectedRequest)
            ->willReturn($mockResponse);

        $this->expectedLogs->info('GET https://loc.geopunt.be/v4/Location?q=Martelarenplein%201,%203000%20Leuven,%20BE responded with status code 200 and body {}');
        $this->expectedLogs->error(
            'Response body did not match the expected JSON schema. Did the API introduce a breaking change?',
            [
                'errors' => [
                    '/' => [
                        0 => 'The required properties (LocationResult) are missing',
                    ],
                ],
            ]
        );

        $actualParsedAddress = $this->geopuntAddressParser->parse($address);

        $this->assertNull($actualParsedAddress);
        $this->assertLogs();
    }

    /**
     * @test
     */
    public function it_can_handle_missing_properties_inside_LocationResult(): void
    {
        $address = 'Martelarenplein 1, 3000 Leuven, BE';

        $expectedRequest = new Request(
            'GET',
            'https://loc.geopunt.be/v4/Location?q=Martelarenplein%201,%203000%20Leuven,%20BE'
        );

        $mockResponseData = [
            'LocationResult' => [
                (object) [],
            ],
        ];
        $mockResponse = new Response(200, [], Json::encode($mockResponseData));

        $this->httpClient->expects($this->once())
            ->method('sendRequest')
            ->with($expectedRequest)
            ->willReturn($mockResponse);

        $this->expectedLogs->info('GET https://loc.geopunt.be/v4/Location?q=Martelarenplein%201,%203000%20Leuven,%20BE responded with status code 200 and body {"LocationResult":[{}]}');
        $this->expectedLogs->error(
            'Response body did not match the expected JSON schema. Did the API introduce a breaking change?',
            [
                'errors' => [
                    '/LocationResult/0' => [
                        0 => 'The required properties (Municipality, Zipcode, Thoroughfarename, Housenumber) are missing',
                    ],
                ],
            ]
        );

        $actualParsedAddress = $this->geopuntAddressParser->parse($address);

        $this->assertNull($actualParsedAddress);
        $this->assertLogs();
    }

    /**
     * @test
     */
    public function it_can_handle_incorrect_property_values_inside_LocationResult(): void
    {
        $address = 'Martelarenplein 1, 3000 Leuven, BE';

        $expectedRequest = new Request(
            'GET',
            'https://loc.geopunt.be/v4/Location?q=Martelarenplein%201,%203000%20Leuven,%20BE'
        );

        $mockResponseData = [
            'LocationResult' => [
                [
                    'Municipality' => 8,
                    'Zipcode' => null,
                    'Thoroughfarename' => false,
                    'Housenumber' => [],
                ],
            ],
        ];
        $mockResponse = new Response(200, [], Json::encode($mockResponseData));

        $this->httpClient->expects($this->once())
            ->method('sendRequest')
            ->with($expectedRequest)
            ->willReturn($mockResponse);

        $this->expectedLogs->info('GET https://loc.geopunt.be/v4/Location?q=Martelarenplein%201,%203000%20Leuven,%20BE responded with status code 200 and body {"LocationResult":[{"Municipality":8,"Zipcode":null,"Thoroughfarename":false,"Housenumber":[]}]}');
        $this->expectedLogs->error(
            'Response body did not match the expected JSON schema. Did the API introduce a breaking change?',
            [
                'errors' => [
                    '/LocationResult/0/Municipality' => [
                        0 => 'The data (integer) must match the type: string',
                    ],
                    '/LocationResult/0/Thoroughfarename' => [
                        0 => 'The data (boolean) must match the type: string',
                        1 => 'The data (boolean) must match the type: null',
                    ],
                    '/LocationResult/0/Housenumber' => [
                        0 => 'The data (array) must match the type: string',
                        1 => 'The data (array) must match the type: null',
                    ],
                ],
            ]
        );

        $actualParsedAddress = $this->geopuntAddressParser->parse($address);

        $this->assertNull($actualParsedAddress);
        $this->assertLogs();
    }

    /**
     * @test
     */
    public function it_can_handle_an_empty_LocationResult(): void
    {
        $address = 'Martelarenplein 1, 3000 Leuven, BE';

        $expectedRequest = new Request(
            'GET',
            'https://loc.geopunt.be/v4/Location?q=Martelarenplein%201,%203000%20Leuven,%20BE'
        );

        $mockResponseData = [
            'LocationResult' => [],
        ];
        $mockResponse = new Response(200, [], Json::encode($mockResponseData));

        $this->httpClient->expects($this->once())
            ->method('sendRequest')
            ->with($expectedRequest)
            ->willReturn($mockResponse);

        $this->expectedLogs->info('GET https://loc.geopunt.be/v4/Location?q=Martelarenplein%201,%203000%20Leuven,%20BE responded with status code 200 and body {"LocationResult":[]}');
        $this->expectedLogs->info('Response body did not contain any array items inside "LocationResult" property. Either the address is not located in Belgium, or it is not recognized as an official address.');

        $actualParsedAddress = $this->geopuntAddressParser->parse($address);

        $this->assertNull($actualParsedAddress);
        $this->assertLogs();
    }

    /**
     * @test
     */
    public function it_can_handle_an_empty_zipcode(): void
    {
        $address = 'Marguerite Durassquare, 1000 Brussel, BE';

        $expectedRequest = new Request(
            'GET',
            'https://loc.geopunt.be/v4/Location?q=Marguerite%20Durassquare,%201000%20Brussel,%20BE'
        );

        $mockResponseData = [
            'LocationResult' => [
                [
                    'Municipality' => 'Bruxelles',
                    'Zipcode' => null,
                    'Thoroughfarename' => 'Marguerite Durassquare',
                    'Housenumber' => null,
                ],
            ],
        ];

        $mockResponse = new Response(200, [], Json::encode($mockResponseData));

        $this->httpClient->expects($this->once())
            ->method('sendRequest')
            ->with($expectedRequest)
            ->willReturn($mockResponse);

        $this->expectedLogs->info('GET https://loc.geopunt.be/v4/Location?q=Marguerite%20Durassquare,%201000%20Brussel,%20BE responded with status code 200 and body {"LocationResult":[{"Municipality":"Bruxelles","Zipcode":null,"Thoroughfarename":"Marguerite Durassquare","Housenumber":null}]}');
        $this->expectedLogs->info('Successfully parsed response body.');

        $expectedParsedAddress = new ParsedAddress(
            'Marguerite Durassquare',
            null,
            null,
            'Bruxelles'
        );

        $actualParsedAddress = $this->geopuntAddressParser->parse($address);

        $this->assertEquals($expectedParsedAddress, $actualParsedAddress);
        $this->assertLogs();
    }

    /**
     * @test
     */
    public function it_can_handle_an_empty_thoroughfare(): void
    {
        $address = 'Marguerite Durassquare, 1000 Brussel, BE';

        $expectedRequest = new Request(
            'GET',
            'https://loc.geopunt.be/v4/Location?q=Marguerite%20Durassquare,%201000%20Brussel,%20BE'
        );

        $mockResponseData = [
            'LocationResult' => [
                [
                    'Municipality' => 'Bruxelles',
                    'Zipcode' => '1000',
                    'Thoroughfarename' => null,
                    'Housenumber' => null,
                ],
            ],
        ];

        $mockResponse = new Response(200, [], Json::encode($mockResponseData));

        $this->httpClient->expects($this->once())
            ->method('sendRequest')
            ->with($expectedRequest)
            ->willReturn($mockResponse);

        $this->expectedLogs->info('GET https://loc.geopunt.be/v4/Location?q=Marguerite%20Durassquare,%201000%20Brussel,%20BE responded with status code 200 and body {"LocationResult":[{"Municipality":"Bruxelles","Zipcode":"1000","Thoroughfarename":null,"Housenumber":null}]}');
        $this->expectedLogs->info('Successfully parsed response body.');

        $expectedParsedAddress = new ParsedAddress(
            null,
            null,
            '1000',
            'Bruxelles'
        );

        $actualParsedAddress = $this->geopuntAddressParser->parse($address);

        $this->assertEquals($expectedParsedAddress, $actualParsedAddress);
        $this->assertLogs();
    }

    private function assertLogs(): void
    {
        $this->assertEquals($this->expectedLogs->getLogs(), $this->logger->getLogs());
    }
}
