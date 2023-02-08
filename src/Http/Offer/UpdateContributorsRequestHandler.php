<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

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
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddresses;
use CultuurNet\UDB3\Offer\OfferRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class UpdateContributorsRequestHandler implements RequestHandlerInterface
{
    private OfferRepository $offerRepository;

    private ContributorRepositoryInterface $contributorRepository;

    public function __construct(OfferRepository $offerRepository, ContributorRepositoryInterface $contributorRepository)
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

        $parser = RequestBodyParserFactory::createBaseParser(
            new JsonSchemaValidatingRequestBodyParser(
                JsonSchemaLocator::getSchemaFileByOfferType(
                    $offerType,
                    JsonSchemaLocator::EVENT_CONTRIBUTORS_PUT,
                    JsonSchemaLocator::PLACE_CONTRIBUTORS_PUT
                )
            ),
            new DenormalizingRequestBodyParser(
                new ContributorDenormalizer(),
                EmailAddresses::class
            )
        );

        /** @var EmailAddresses $emails */
        $emails = $parser->parse($request)->getParsedBody();

        $this->contributorRepository->overwriteContributors(new UUID($offerId), $emails);

        return new NoContentResponse();
    }
}
