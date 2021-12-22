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
use CultuurNet\UDB3\Organizer\Commands\UpdateImage;
use CultuurNet\UDB3\Organizer\Commands\UpdateImages;
use CultuurNet\UDB3\Organizer\Serializers\UpdateImageDenormalizer;
use CultuurNet\UDB3\Organizer\Serializers\UpdateImagesDenormalizer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class UpdateImagesRequestHandler implements RequestHandlerInterface
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
            new JsonSchemaValidatingRequestBodyParser(JsonSchemaLocator::ORGANIZER_IMAGES_PATCH),
            new DenormalizingRequestBodyParser(
                new UpdateImagesDenormalizer(new UpdateImageDenormalizer($organizerId)),
                UpdateImages::class
            )
        );

        /** @var UpdateImage[] $updateImages */
        $updateImages = $requestBodyParser->parse($request)->getParsedBody();

        foreach ($updateImages as $updateImage) {
            $this->commandBus->dispatch($updateImage);
        }

        return new NoContentResponse();
    }
}
