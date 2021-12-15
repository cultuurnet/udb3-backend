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
use CultuurNet\UDB3\Organizer\Commands\UpdateDescription;
use CultuurNet\UDB3\Organizer\Serializers\UpdateDescriptionDenormalizer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class UpdateDescriptionRequestHandler implements RequestHandlerInterface
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
        $language = $routeParameters->getLanguage();

        $requestBodyParser = RequestBodyParserFactory::createBaseParser(
            new JsonSchemaValidatingRequestBodyParser(JsonSchemaLocator::ORGANIZER_DESCRIPTION_PUT),
            new DenormalizingRequestBodyParser(
                new UpdateDescriptionDenormalizer($organizerId, $language),
                UpdateDescription::class
            )
        );

        $updateDescription = $requestBodyParser->parse($request)->getParsedBody();

        $this->commandBus->dispatch($updateDescription);

        return new NoContentResponse();
    }
}
