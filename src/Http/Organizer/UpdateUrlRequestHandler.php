<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Organizer;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\EventSourcing\DBAL\UniqueConstraintException;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\Body\DenormalizingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaLocator;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaValidatingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParserFactory;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Organizer\Commands\UpdateWebsite;
use CultuurNet\UDB3\Organizer\Serializers\UpdateWebsiteDenormalizer;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class UpdateUrlRequestHandler implements RequestHandlerInterface
{
    private CommandBus $commandBus;

    public function __construct(CommandBus $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    public function handle(ServerRequestInterface $request): NoContentResponse
    {
        $routeParameters = new RouteParameters($request);
        $organizerId = $routeParameters->getOrganizerId();

        $requestBodyParser = RequestBodyParserFactory::createBaseParser(
            new JsonSchemaValidatingRequestBodyParser(JsonSchemaLocator::ORGANIZER_URL_PUT),
            new DenormalizingRequestBodyParser(
                new UpdateWebsiteDenormalizer($organizerId),
                UpdateWebsite::class
            )
        );

        /** @var UpdateWebsite $updateWebsite */
        $updateWebsite = $requestBodyParser->parse($request)->getParsedBody();

        try {
            $this->commandBus->dispatch($updateWebsite);
        } catch (UniqueConstraintException $e) {
            // Saving the organizer to the event store can trigger a UniqueConstraintException if the URL is already in
            // use by another organizer. This is intended but we need to return a prettier error for API integrators.
            throw ApiProblem::duplicateUrl($updateWebsite->getWebsite()->toString(), $e->getDuplicateValue());
        }

        return new NoContentResponse();
    }
}
