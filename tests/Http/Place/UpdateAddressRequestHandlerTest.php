<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Place;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Model\ValueObject\Geography\Address;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Locality;
use CultuurNet\UDB3\Model\ValueObject\Geography\PostalCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Street;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Place\Commands\UpdateAddress;
use Iterator;
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
     * @dataProvider provideInvalidRequestDataScenarios
     */
    public function it_throws_on_invalid_request_bodies(array $requestData, ApiProblem $expectedProblem): void
    {
        $updateAddressRequest = $this->psr7RequestBuilder
            ->withRouteParameter('placeId', self::PLACE_ID)
            ->withRouteParameter('language', 'nl')
            ->withJsonBodyFromArray($requestData)
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            $expectedProblem,
            fn () => $this->updateAddressRequestHandler->handle($updateAddressRequest)
        );
    }

    public function provideInvalidRequestDataScenarios(): Iterator
    {
        yield 'missing properties' => [
            'requestData' => ['properties' => 'missing'],
            'expectedProblem' => ApiProblem::bodyInvalidData(
                new SchemaError('/', 'The required properties (addressCountry, addressLocality, postalCode, streetAddress) are missing')
            ),
        ];

        yield 'empty properties' => [
            'requestData' => [
                'streetAddress' => '',
                'postalCode' => '',
                'addressLocality' => '',
                'addressCountry' => '',
            ],
            'expectedProblem' => ApiProblem::bodyInvalidData(
                new SchemaError('/addressCountry', 'Minimum string length is 1, found 0'),
                new SchemaError('/addressLocality', 'Minimum string length is 1, found 0'),
                new SchemaError('/postalCode', 'Minimum string length is 1, found 0'),
                new SchemaError('/streetAddress', 'Minimum string length is 1, found 0'),
            ),
        ];

        yield 'incorrect country code' => [
            'requestData' => [
                'streetAddress' => 'Veldstraat 11',
                'postalCode' => '9000',
                'addressLocality' => 'Gent',
                'addressCountry' => 'BEL',
            ],
            'expectedProblem' => ApiProblem::bodyInvalidData(
                new SchemaError('/addressCountry', 'Maximum string length is 2, found 3')
            ),
        ];
    }
}
