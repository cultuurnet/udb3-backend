<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Organizer;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\Body\DenormalizingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaLocator;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaValidatingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParserFactory;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Offer\ImageMustBeLinkedException;
use CultuurNet\UDB3\Organizer\Commands\UpdateMainImage;
use CultuurNet\UDB3\Organizer\Serializers\UpdateMainImageDenormalizer;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class UpdateMainImageRequestHandler implements RequestHandlerInterface
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
            new LegacyMainImageRequestBodyParser(),
            new JsonSchemaValidatingRequestBodyParser(JsonSchemaLocator::ORGANIZER_MAIN_IMAGE_PUT),
            new DenormalizingRequestBodyParser(new UpdateMainImageDenormalizer($organizerId), UpdateMainImage::class)
        );

        /** @var UpdateMainImage $updateMainImage */
        $updateMainImage = $requestBodyParser->parse($request)->getParsedBody();

        try {
            $this->commandBus->dispatch($updateMainImage);
        } catch (ImageMustBeLinkedException $exception) {
            throw ApiProblem::imageMustBeLinkedToResource($updateMainImage->getImageId()->toString());
        }

        return new NoContentResponse();
    }
}
