<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\Repository\AggregateNotFoundException;
use CultuurNet\UDB3\Contributor\ContributorRepository;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddresses;
use CultuurNet\UDB3\Offer\OfferRepository;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\Permission\PermissionVoter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class GetContributorsRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;
    use AssertJsonResponseTrait;

    private GetContributorsRequestHandler $getContributorsRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    private OfferRepository&MockObject $offerRepository;

    private ContributorRepository&MockObject $contributorRepository;

    private PermissionVoter&MockObject $permissionVoter;

    private ?string $currentUserId;

    public function setUp(): void
    {
        $this->offerRepository = $this->createMock(OfferRepository::class);
        $this->contributorRepository = $this->createMock(ContributorRepository::class);
        $this->permissionVoter = $this->createMock(PermissionVoter::class);
        $this->currentUserId = '0b7ca1e2-e2e5-467f-9e7d-0cd481d66ee5';

        $this->getContributorsRequestHandler = new GetContributorsRequestHandler(
            $this->offerRepository,
            $this->contributorRepository,
            $this->permissionVoter,
            $this->currentUserId
        );

        $this->psr7RequestBuilder = new Psr7RequestBuilder();
    }

    /**
     * @test
     * @dataProvider offerDataProvider
     */
    public function it_handles_getting_contributors(
        OfferType $offerType,
        string $offerRouteParameter,
        string $offerId
    ): void {
        $this->permissionVoter->expects($this->once())
            ->method('isAllowed')
            ->with(Permission::aanbodBewerken(), $offerId, $this->currentUserId)
            ->willReturn(true);

        $this->contributorRepository->expects($this->once())
            ->method('getContributors')
            ->with(new Uuid($offerId))
            ->willReturn(
                EmailAddresses::fromArray(
                    [
                        new EmailAddress('info@gent.be'),
                        new EmailAddress('an@gent.be'),
                    ]
                )
            );

        $getContributorsRequest = $this->psr7RequestBuilder
            ->withRouteParameter('offerType', $offerRouteParameter)
            ->withRouteParameter('offerId', $offerId)
            ->build('GET');

        $response = $this->getContributorsRequestHandler->handle($getContributorsRequest);

        $this->assertJsonResponse(
            new JsonResponse(['info@gent.be','an@gent.be']),
            $response
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

        $getContributorsRequest = $this->psr7RequestBuilder
            ->withRouteParameter('offerType', $offerRouteParameter)
            ->withRouteParameter('offerId', $offerId)
            ->build('GET');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::offerNotFound($offerType, $offerId),
            fn () => $this->getContributorsRequestHandler->handle($getContributorsRequest)
        );
    }

    /**
     * @test
     * @dataProvider offerDataProvider
     */
    public function it_handles_forbidden_offers(
        OfferType $offerType,
        string $offerRouteParameter,
        string $offerId
    ): void {
        $this->permissionVoter->expects($this->once())
            ->method('isAllowed')
            ->with(Permission::aanbodBewerken(), $offerId, $this->currentUserId)
            ->willReturn(false);

        $getContributorsRequest = $this->psr7RequestBuilder
            ->withRouteParameter('offerType', $offerRouteParameter)
            ->withRouteParameter('offerId', $offerId)
            ->build('GET');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::forbidden(
                sprintf(
                    'User %s has no permission "%s" on resource %s',
                    $this->currentUserId,
                    Permission::aanbodBewerken()->toString(),
                    $offerId
                )
            ),
            fn () => $this->getContributorsRequestHandler->handle($getContributorsRequest)
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
