<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Contributor\ContributorRepositoryInterface;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class ManageContributors implements RequestHandlerInterface
{
    private ContributorRepositoryInterface $contributorRepository;

    public function __construct(ContributorRepositoryInterface $contributorRepository)
    {
        $this->contributorRepository = $contributorRepository;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $offerId = $routeParameters->getOfferId();
        // TODO: Use Json Scheme
        $emails = JSON::decode($request->getBody()->getContents());

        $this->contributorRepository->deleteContributors(new UUID($offerId));

        try {
            foreach ($emails as $email) {
                $this->contributorRepository->addContributor(
                    new UUID($offerId),
                    new EmailAddress($email)
                );
            }
            return new NoContentResponse();
        } catch (\InvalidArgumentException $exception) {
            throw ApiProblem::bodyInvalidData(
                new SchemaError('/contributors', $exception->getMessage())
            );
        }
    }
}
