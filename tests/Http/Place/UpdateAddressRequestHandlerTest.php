<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Place;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\Deserializer\DataValidationException;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Place\Commands\UpdateAddress;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class UpdateAddressRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;
    use AssertJsonResponseTrait;

    private const PLACE_ID = 'd2a039e9-f4d6-4080-ae33-a106b5d3d47b';

    private TraceableCommandBus $commandBus;

    private UpdateAddressRequestHandler $updateAddressRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();

        $this->updateAddressRequestHandler = new UpdateAddressRequestHandler(
            $this->commandBus
        );

        $this->psr7RequestBuilder = new Psr7RequestBuilder();

        $this->commandBus->record();
    }

    /**
     * @test
     */
    public function it_handles_updating_the_address(): void
    {
        $updateAddressRequest = $this->psr7RequestBuilder
            ->withRouteParameter('placeId', self::PLACE_ID)
            ->withRouteParameter('language', 'nl')
            ->withBodyFromString(
                '{
                    "streetAddress": "Veldstraat 11",
                    "postalCode": "9000",
                    "addressLocality": "Gent",
                    "addressCountry": "BE"
                }'
            )
            ->build('PUT');

        $response = $this->updateAddressRequestHandler->handle($updateAddressRequest);

        $this->assertEquals(
            [
                new UpdateAddress(
                    self::PLACE_ID,
                    new Address(
                        new Street('Veldstraat 11'),
                        new PostalCode('9000'),
                        new Locality('Gent'),
                        new CountryCode('BE')
                    ),
                    new Language('nl')
                ),
            ],
            $this->commandBus->getRecordedCommands()
        );

        $this->assertJsonResponse(
            new NoContentResponse(),
            $response
        );
    }

    /**
     * @test
     */
    public function it_throws_on_invalid_country(): void
    {
        $updateAddressRequest = $this->psr7RequestBuilder
            ->withRouteParameter('placeId', self::PLACE_ID)
            ->withRouteParameter('language', 'nl')
            ->withBodyFromString(
                '{
                    "streetAddress": "Veldstraat 11",
                    "postalCode": "9000",
                    "addressLocality": "Gent",
                    "addressCountry": "BEL"
                }'
            )
            ->build('PUT');

        $this->expectException(InvalidArgumentException::class);

        $this->updateAddressRequestHandler->handle($updateAddressRequest);
    }

    /**
     * @test
     * @dataProvider emptyValueDataProvider
     */
    public function it_throws_on_empty_values(array $emptyValueAddress): void
    {
        $updateAddressRequest = $this->psr7RequestBuilder
            ->withRouteParameter('placeId', self::PLACE_ID)
            ->withRouteParameter('language', 'nl')
            ->withBodyFromString(
                Json::encode($emptyValueAddress)
            )
            ->build('PUT');

        $this->expectException(DataValidationException::class);

        $this->updateAddressRequestHandler->handle($updateAddressRequest);
    }


    public function emptyValueDataProvider(): array
    {
        return [
            [
                [
                    'streetAddress' => '',
                    'postalCode' => '9000',
                    'addressLocality' => 'Gent',
                    'addressCountry' => 'BE',
                    ],
                ],
            [
                [
                    'streetAddress' => 'Veldstraat 11',
                    'postalCode' => '',
                    'addressLocality' => 'Gent',
                    'addressCountry' => 'BE',
                    ],
                ],
            [
                [
                    'streetAddress' => 'Veldstraat 11',
                    'postalCode' => '9000',
                    'addressLocality' => '',
                    'addressCountry' => 'BE',
                    ],
                ],
            [
                [
                    'streetAddress' => 'Veldstraat 11',
                    'postalCode' => '9000',
                    'addressLocality' => 'Gent',
                    'addressCountry' => '',
                ],
            ],
        ];
    }
}
