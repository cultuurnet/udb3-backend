<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\Repository\AggregateNotFoundException;
use CultuurNet\UDB3\Contributor\ContributorRepository;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Offer\OfferRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class GetContributorsRequestHandler implements RequestHandlerInterface
{
    private OfferRepository $offerRepository;

    private ContributorRepository $contributorRepository;

    public function __construct(OfferRepository $offerRepository, ContributorRepository $contributorRepository)
    {
        $this->offerRepository = $offerRepository;
        $this->contributorRepository = $contributorRepository;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $offerType = $routeParameters->getOfferType();
        $offerId = $routeParameters->getOfferId();

        try {
            $this->offerRepository->load($offerId);
        } catch (AggregateNotFoundException $exception) {
            throw ApiProblem::offerNotFound($offerType, $offerId);
        }

        $results = $this->contributorRepository->getContributors(new UUID($offerId))->toArray();

        return new JsonResponse(
            array_map(
                fn (EmailAddress $email) => $email->toString(),
                $results
            )
        );
    }
}
