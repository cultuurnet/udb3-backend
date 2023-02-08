<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\Repository\AggregateNotFoundException;
use CultuurNet\UDB3\Contributor\ContributorRepositoryInterface;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Offer\OfferRepository;
use CultuurNet\UDB3\Offer\OfferType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ManageContributorsRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;
    use AssertJsonResponseTrait;

    private ManageContributorsRequestHandler $manageContributorsRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    /**
     * @var OfferRepository|MockObject
     */
    private $offerRepository;

    public function setUp(): void
    {
        $this->offerRepository = $this->createMock(OfferRepository::class);
        $this->manageContributorsRequestHandler = new ManageContributorsRequestHandler(
            $this->offerRepository,
            $this->createMock(ContributorRepositoryInterface::class)
        );

        $this->psr7RequestBuilder = new Psr7RequestBuilder();
    }

    /**
     * @test
     * @dataProvider offerDataProvider
     */
    public function it_handles_managing_contributors(
        OfferType $offerType,
        string $offerRouteParameter,
        string $offerId
    ): void {
        $manageContributorsRequest = $this->psr7RequestBuilder
            ->withRouteParameter('offerType', $offerRouteParameter)
            ->withRouteParameter('offerId', $offerId)
            ->withJsonBodyFromArray(
                [
                    'jan@gent.be',
                    'piet@gent.be',
                    'an@gent.be',
                ]
            )
            ->build('PUT');

        $response = $this->manageContributorsRequestHandler->handle($manageContributorsRequest);

        $this->assertJsonResponse(
            new NoContentResponse(),
            $response
        );
    }

    /**
     * @test
     * @dataProvider offerDataProvider
     */
    public function it_handles_invalid_emails(
        OfferType $offerType,
        string $offerRouteParameter,
        string $offerId
    ): void {
        $invalidContributorsRequest = $this->psr7RequestBuilder
            ->withRouteParameter('offerType', $offerRouteParameter)
            ->withRouteParameter('offerId', $offerId)
            ->withJsonBodyFromArray(
                [
                    '09/1231212',
                    'piet@gent.be',
                    'an@gent.be',
                ]
            )
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError('/0', 'The data must match the \'email\' format')
            ),
            fn () => $this->manageContributorsRequestHandler->handle($invalidContributorsRequest)
        );
    }

    /**
     * @test
     * @dataProvider offerDataProvider
     */
    public function it_handles_unknown_offers(
        OfferType $offerType,
        string $offerRouteParameter,
        string $offerId
    ): void {
        $this->offerRepository->expects($this->once())
            ->method('load')
            ->with($offerId)
            ->willThrowException(new AggregateNotFoundException());

        $unkownOfferRequest = $this->psr7RequestBuilder
            ->withRouteParameter('offerType', $offerRouteParameter)
            ->withRouteParameter('offerId', $offerId)
            ->withJsonBodyFromArray(
                [
                    'piet@gent.be',
                    'an@gent.be',
                    '09/1231212',
                ]
            )
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::offerNotFound($offerType, $offerId),
            fn () => $this->manageContributorsRequestHandler->handle($unkownOfferRequest)
        );
    }

    public function offerDataProvider(): array
    {
        return [
            'event' => [
                OfferType::event(),
                'events',
                '4c47cbf8-8406-4af6-b6e7-fddd78e0efd8',
            ],
            'place' => [
                OfferType::place(),
                'places',
                '4ecb33d8-8068-45c9-a58e-e5fb767cb08a',
            ],
        ];
    }
}
