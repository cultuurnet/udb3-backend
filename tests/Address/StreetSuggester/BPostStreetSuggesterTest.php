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
use Psr\Log\LoggerInterface;

final class BPostStreetSuggesterTest extends TestCase
{
    private const DOMAIN = 'https://foobar.com';
    private const STAGE = 'stage';
    private const TOKEN = 'token';

    private ClientInterface&MockObject $client;
    private StreetSuggester $streetSuggester;
    private LoggerInterface&MockObject $logger;

    protected function setUp(): void
    {
        $this->client = $this->createMock(ClientInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->streetSuggester = new BPostStreetSuggester(
            $this->client,
            self::DOMAIN,
            self::STAGE,
            self::TOKEN,
            $this->logger
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
                        ->withPath('/' . self::STAGE . '/externalMailingAddressProofingRest/autocomplete/street')
                        ->withQuery(http_build_query([
                            'id' => '7',
                            'q' => $streetQuery,
                            'postalCode' => $postalCode,
                            'locality' => $locality,
                            'maxNumberOfSuggestions' => 5,
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

    /**
     * @test
     */
    public function itCanHandleVariousLimits(): void
    {
        $postalCode = '9000';
        $locality = 'Gent';
        $streetQuery = 'maria';
        $limit = 2;

        $this->client->expects($this->once())
            ->method('sendRequest')
            ->with(
                new Request(
                    'GET',
                    (new Uri(self::DOMAIN))
                        ->withPath('/' . self::STAGE . '/externalMailingAddressProofingRest/autocomplete/street')
                        ->withQuery(http_build_query([
                            'id' => '7',
                            'q' => $streetQuery,
                            'postalCode' => $postalCode,
                            'locality' => $locality,
                            'maxNumberOfSuggestions' => 2,
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
            ],
            $this->streetSuggester->suggest($postalCode, $locality, $streetQuery, $limit)
        );
    }

    /**
     * @test
     */
    public function itTakesIntoAccountStatusCode(): void
    {
        $postalCode = '9000';
        $locality = 'Gent';
        $streetQuery = 'maria';

        $this->client->expects($this->once())
            ->method('sendRequest')
            ->willReturn(
                new Response(400)
            );

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'BPost Street Suggester returned non-200 status code',
                $this->arrayHasKey('status_code')
            );

        $this->assertEquals(
            [],
            $this->streetSuggester->suggest($postalCode, $locality, $streetQuery)
        );
    }
}
