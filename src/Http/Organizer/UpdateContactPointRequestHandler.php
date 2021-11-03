<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Organizer;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Http\Request\Body\DenormalizingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaLocator;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaValidatingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParserFactory;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Organizer\Commands\UpdateContactPoint;
use CultuurNet\UDB3\Organizer\Serializers\UpdateContactPointDenormalizer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class UpdateContactPointRequestHandler implements RequestHandlerInterface
{
    private CommandBus $commandBus;

    public function __construct(CommandBus $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $organizerId = $routeParameters->getOrganizerId();

        $requestBodyParser = RequestBodyParserFactory::createBaseParser(
            new LegacyContactPointRequestBodyParser(),
            new JsonSchemaValidatingRequestBodyParser(JsonSchemaLocator::ORGANIZER_CONTACT_POINT_PUT),
            new DenormalizingRequestBodyParser(
                new UpdateContactPointDenormalizer($organizerId),
                UpdateContactPoint::class
            )
        );

        $updateTitle = $requestBodyParser->parse($request)->getParsedBody();

        $this->commandBus->dispatch($updateTitle);

        return new NoContentResponse();
    }
}
