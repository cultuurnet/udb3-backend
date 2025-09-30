<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Address\StreetSuggester;

use CultuurNet\UDB3\Json;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;

final class BPostStreetSuggesterTest extends TestCase
{
    public const DOMAIN = 'https://foobar.com';

    public const TOKEN = 'token';

    /**
     * @var ClientInterface&MockObject
     */
    private $client;
    private StreetSuggester $streetSuggester;

    public function setUp(): void
    {
        $this->client = $this->createMock(ClientInterface::class);
        $this->streetSuggester = new BPostStreetSuggester(
            $this->client,
            self::DOMAIN,
            self::TOKEN
        );
    }

    /**
     * @test
     */
    public function itReturnsFormattedStreets(): void
    {
        $postalCode = '9000';
        $locality = 'Gent';
        $streetQuery = 'maria';

        $this->client->expects($this->once())
            ->method('sendRequest')
            ->with(
                new Request(
                    'GET',
                    (new Uri(self::DOMAIN))
                        ->withPath('/roa-info-st2/externalMailingAddressProofingRest/autocomplete/street')
                        ->withQuery(http_build_query([
                            'id' => '7',
                            'q' => $streetQuery,
                            'postalCode' => $postalCode,
                            'locality' => $locality,
                        ])),
                    [
                        'x-api-key' => self::TOKEN,
                    ]
                )
            )
            ->willReturn(
                new Response(
                    200,
                    [],
                    Json::encode(
                        [
                            'response' => [
                                'sequenceNumber' => 7,
                                'topSuggestions' => [
                                    [
                                        'address' => [
                                            'detectedLanguage' => 'nl',
                                            'string' => 'KONINGIN MARIA HENDRIKAPLEIN',
                                            'searchBarString' => 'KONINGIN MARIA HENDRIKAPLEIN',
                                            'streetName' => 'KONINGIN MARIA HENDRIKAPLEIN',
                                            'municipalityName' => 'GENT',
                                            'postalCode' => '9000',
                                            'localityName' => 'GENT',
                                        ],
                                    ],
                                    [
                                        'address' => [
                                            'detectedLanguage' => 'nl',
                                            'string' => 'MARIALAND',
                                            'searchBarString' => 'MARIALAND',
                                            'streetName' => 'MARIALAND',
                                            'municipalityName' => 'GENT',
                                            'postalCode' => '9000',
                                            'localityName' => 'GENT',
                                        ],
                                    ],
                                    [
                                        'address' => [
                                            'detectedLanguage' => 'nl',
                                            'string' => 'MARIA-THERESIASTRAAT',
                                            'searchBarString' => 'MARIA-THERESIASTRAAT',
                                            'streetName' => 'MARIA-THERESIASTRAAT',
                                            'municipalityName' => 'GENT',
                                            'postalCode' => '9000',
                                            'localityName' => 'GENT',
                                        ],
                                    ],
                                    [
                                        'address' => [
                                            'detectedLanguage' => 'nl',
                                            'string' => 'MARIA VAN BOERGONDIËSTRAAT',
                                            'searchBarString' => 'MARIA VAN BOERGONDIËSTRAAT',
                                            'streetName' => 'MARIA VAN BOERGONDIËSTRAAT',
                                            'municipalityName' => 'GENT',
                                            'postalCode' => '9000',
                                            'localityName' => 'GENT',
                                        ],
                                    ],
                                ],
                            ],
                        ]
                    )
                )
            );

        $this->assertEquals(
            [
                'Koningin Maria Hendrikaplein',
                'Marialand',
                'Maria-Theresiastraat',
                'Maria Van Boergondiëstraat',
            ],
            $this->streetSuggester->suggest($postalCode, $locality, $streetQuery)
        );
    }
}
