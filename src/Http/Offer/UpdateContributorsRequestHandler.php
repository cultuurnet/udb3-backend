<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Event\Events\UpdateEventContributors;
use CultuurNet\UDB3\Http\Deserializer\ContributorDenormalizer;
use CultuurNet\UDB3\Http\Request\Body\DenormalizingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaLocator;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaValidatingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParserFactory;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddresses;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Place\Events\UpdatePlaceContributors;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class UpdateContributorsRequestHandler implements RequestHandlerInterface
{
    private CommandBus $commandBus;

    public function __construct(CommandBus $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $offerType = $routeParameters->getOfferType();
        $offerId = $routeParameters->getOfferId();

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

        if ($offerType === OfferType::event()) {
            $updateContributors = new UpdateEventContributors($offerId, $emails);
        } else {
            $updateContributors = new UpdatePlaceContributors($offerId, $emails);
        }
        $this->commandBus->dispatch(
            $updateContributors
        );

        return new NoContentResponse();
    }
}