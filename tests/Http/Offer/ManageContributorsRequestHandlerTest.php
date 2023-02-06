<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Contributor\ContributorRepositoryInterface;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use PHPUnit\Framework\TestCase;

final class ManageContributorsRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;
    use AssertJsonResponseTrait;

    private ManageContributorsRequestHandler $manageContributorsRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    public function setUp(): void
    {
        $this->manageContributorsRequestHandler = new ManageContributorsRequestHandler(
            $this->createMock(ContributorRepositoryInterface::class)
        );

        $this->psr7RequestBuilder = new Psr7RequestBuilder();
    }

    /**
     * @test
     */
    public function it_handles_managing_contributors(): void
    {
        $manageContributorsRequest = $this->psr7RequestBuilder
            ->withRouteParameter('offerType', 'events')
            ->withRouteParameter('offerId', '4c47cbf8-8406-4af6-b6e7-fddd78e0efd8')
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
     */
    public function it_handles_invalid_emails(): void
    {
        $invalidContributorsRequest = $this->psr7RequestBuilder
            ->withRouteParameter('offerType', 'events')
            ->withRouteParameter('offerId', '4c47cbf8-8406-4af6-b6e7-fddd78e0efd8')
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
                new SchemaError('/contributors', 'Given string is not a valid e-mail address.')
            ),
            fn () => $this->manageContributorsRequestHandler->handle($invalidContributorsRequest)
        );
    }
}
