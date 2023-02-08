<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Organizer;

use Broadway\Repository\AggregateNotFoundException;
use CultuurNet\UDB3\Contributor\ContributorRepositoryInterface;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Deserializer\ContributorDenormalizer;
use CultuurNet\UDB3\Http\Request\Body\DenormalizingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaLocator;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaValidatingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParserFactory;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddresses;
use CultuurNet\UDB3\Organizer\OrganizerRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class ManageContributorsRequestHandler implements RequestHandlerInterface
{
    private OrganizerRepository $organizerRepository;

    private ContributorRepositoryInterface $contributorRepository;

    public function __construct(
        OrganizerRepository $organizerRepository,
        ContributorRepositoryInterface $contributorRepository
    ) {
        $this->organizerRepository = $organizerRepository;
        $this->contributorRepository = $contributorRepository;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $offerId = $routeParameters->getOfferId();

        try {
            $this->organizerRepository->load($offerId);
        } catch (AggregateNotFoundException $exception) {
            throw ApiProblem::organizerNotFound($offerId);
        }

        $parser = RequestBodyParserFactory::createBaseParser(
            new JsonSchemaValidatingRequestBodyParser(
                JsonSchemaLocator::ORGANIZER_CONTRIBUTORS_PUT
            ),
            new DenormalizingRequestBodyParser(
                new ContributorDenormalizer(),
                EmailAddresses::class
            )
        );

        /** @var EmailAddresses $emails */
        $emails = $parser->parse($request)->getParsedBody();

        $this->contributorRepository->deleteContributors(new UUID($offerId));

        $emailsAsArray = $emails->toArray();
        /** @var EmailAddress $email */
        foreach ($emailsAsArray as $email) {
            $this->contributorRepository->addContributor(
                new UUID($offerId),
                $email
            );
        }
        return new NoContentResponse();
    }
}
