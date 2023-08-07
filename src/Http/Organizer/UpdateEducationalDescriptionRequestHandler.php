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
use CultuurNet\UDB3\Organizer\Commands\UpdateEducationalDescription;
use CultuurNet\UDB3\Organizer\Serializers\UpdateEducationalDescriptionDenormalizer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class UpdateEducationalDescriptionRequestHandler implements RequestHandlerInterface
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
            new JsonSchemaValidatingRequestBodyParser(JsonSchemaLocator::ORGANIZER_EDUCATIONAL_DESCRIPTION_PUT),
            new DenormalizingRequestBodyParser(
                new UpdateEducationalDescriptionDenormalizer($organizerId, $language),
                UpdateEducationalDescription::class
            )
        );

        $this->commandBus->dispatch($requestBodyParser->parse($request)->getParsedBody());

        return new NoContentResponse();
    }
}
