<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\Import\Taxonomy\Category\CategoryNotFound;
use CultuurNet\UDB3\Offer\Commands\UpdateFacilities;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UpdateFacilitiesRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    private CommandBus&MockObject $commandBus;
    private UpdateFacilitiesRequestHandler $updateFacilitiesRequestHandler;

    protected function setUp(): void
    {
        $this->commandBus = $this->createMock(CommandBus::class);
        $this->updateFacilitiesRequestHandler = new UpdateFacilitiesRequestHandler($this->commandBus);
    }

    /**
     * @test
     * @dataProvider offerTypeDataProvider
     */
    public function it_accepts_a_request_with_json_body_in_the_legacy_format(string $offerType): void
    {
        $json = Json::encode(
            (object) [
                'facilities' => [
                    '3.25.0.0.0',
                    '3.26.0.0.0',
                ],
            ]
        );

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', '84dcd140-a33f-4c52-83ad-7500cecb1652')
            ->withBodyFromString($json)
            ->build('PUT');

        $expectedCommand = new UpdateFacilities(
            '84dcd140-a33f-4c52-83ad-7500cecb1652',
            [
                '3.25.0.0.0',
                '3.26.0.0.0',
            ]
        );

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($expectedCommand);

        $this->updateFacilitiesRequestHandler->handle($request);
    }

    /**
     * @test
     * @dataProvider offerTypeDataProvider
     */
    public function it_accepts_a_request_with_json_body_in_the_simplified_format(string $offerType): void
    {
        $json = Json::encode(
            [
                '3.23.3.0.0',
                '3.13.0.0.0',
            ]
        );

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', '84dcd140-a33f-4c52-83ad-7500cecb1652')
            ->withBodyFromString($json)
            ->build('PUT');

        $expectedCommand = new UpdateFacilities(
            '84dcd140-a33f-4c52-83ad-7500cecb1652',
            [
                '3.23.3.0.0',
                '3.13.0.0.0',
            ]
        );

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($expectedCommand);

        $this->updateFacilitiesRequestHandler->handle($request);
    }

    /**
     * @test
     * @dataProvider offerTypeDataProvider
     */
    public function it_does_not_crash_on_objects_that_do_not_have_the_legacy_facilities_property(string $offerType): void
    {
        $json = Json::encode(
            (object) [
                'foobar' => [],
            ]
        );

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', '84dcd140-a33f-4c52-83ad-7500cecb1652')
            ->withBodyFromString($json)
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError('/', 'The data (object) must match the type: array')
            ),
            fn () => $this->updateFacilitiesRequestHandler->handle($request)
        );
    }

    /**
     * @test
     * @dataProvider offerTypeDataProvider
     */
    public function it_throws_an_api_problem_if_the_array_contains_something_else_than_a_string(string $offerType): void
    {
        $json = Json::encode(
            [
                '3.23.2.0.0',
                (object) ['id' => '3.23.3.0.0'],
                123456,
                false,
                ['3.13.1.0.0'],
                '3.13.0.0.0',
            ]
        );

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', '84dcd140-a33f-4c52-83ad-7500cecb1652')
            ->withBodyFromString($json)
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError('/1', 'The data (object) must match the type: string'),
                new SchemaError('/2', 'The data (integer) must match the type: string'),
                new SchemaError('/3', 'The data (boolean) must match the type: string'),
                new SchemaError('/4', 'The data (array) must match the type: string')
            ),
            fn () => $this->updateFacilitiesRequestHandler->handle($request)
        );
    }

    /**
     * @test
     * @dataProvider offerTypeDataProvider
     */
    public function it_throws_an_api_problem_for_bad_json_syntax(string $offerType): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', '84dcd140-a33f-4c52-83ad-7500cecb1652')
            ->withBodyFromString('{{}')
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidSyntax('JSON'),
            fn () => $this->updateFacilitiesRequestHandler->handle($request)
        );
    }

    /**
     * @test
     * @dataProvider offerTypeDataProvider
     */
    public function it_throws_an_api_problem_for_missing_body(string $offerType): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', '84dcd140-a33f-4c52-83ad-7500cecb1652')
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyMissing(),
            fn () => $this->updateFacilitiesRequestHandler->handle($request)
        );
    }

    /**
     * @test
     * @dataProvider offerTypeDataProvider
     */
    public function it_throws_an_api_problem_for_empty_body(string $offerType): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', '84dcd140-a33f-4c52-83ad-7500cecb1652')
            ->withBodyFromString('')
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyMissing(),
            fn () => $this->updateFacilitiesRequestHandler->handle($request)
        );
    }

    /**
     * @test
     * @dataProvider offerTypeDataProvider
     */
    public function it_throws_an_api_problem_if_a_facility_id_is_invalid(string $offerType): void
    {
        $json = Json::encode(
            [
                '3.23.3.0.0',
                '3.13.0.0.0',
            ]
        );

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', '84dcd140-a33f-4c52-83ad-7500cecb1652')
            ->withBodyFromString($json)
            ->build('PUT');

        $expectedCommand = new UpdateFacilities(
            '84dcd140-a33f-4c52-83ad-7500cecb1652',
            [
                '3.23.3.0.0',
                '3.13.0.0.0',
            ]
        );

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($expectedCommand)
            ->willThrowException(new CategoryNotFound('Facility with id 3.13.0.0.0 not found.'));

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidDataWithDetail('Facility with id 3.13.0.0.0 not found.'),
            fn () => $this->updateFacilitiesRequestHandler->handle($request)
        );
    }

    public function offerTypeDataProvider(): array
    {
        return [
            ['events'],
            ['places'],
        ];
    }
}
