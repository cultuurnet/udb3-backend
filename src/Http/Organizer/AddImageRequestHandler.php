<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Organizer;

use Broadway\CommandHandling\CommandBus;
use Broadway\Repository\AggregateNotFoundException;
use Broadway\Repository\Repository;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\Body\DenormalizingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaLocator;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaValidatingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParserFactory;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Organizer\Commands\AddImage;
use CultuurNet\UDB3\Organizer\Serializers\AddImageDenormalizer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class AddImageRequestHandler implements RequestHandlerInterface
{
    private CommandBus $commandBus;

    private Repository $mediaRepository;

    public function __construct(CommandBus $commandBus, Repository $mediaRepository)
    {
        $this->commandBus = $commandBus;
        $this->mediaRepository = $mediaRepository;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $organizerId = $routeParameters->getOrganizerId();

        $requestBodyParser = RequestBodyParserFactory::createBaseParser(
            new JsonSchemaValidatingRequestBodyParser(JsonSchemaLocator::ORGANIZER_IMAGE_POST),
            new AddMediaObjectPropertiesRequestBodyParser($this->mediaRepository),
            new DenormalizingRequestBodyParser(new AddImageDenormalizer($organizerId), AddImage::class)
        );

        /** @var AddImage $addImage */
        $addImage = $requestBodyParser->parse($request)->getParsedBody();
        $imageId = $addImage->getImage()->getId()->toString();

        try {
            $this->mediaRepository->load($imageId);
        } catch (AggregateNotFoundException $exception) {
            throw ApiProblem::imageNotFound($imageId);
        }

        $this->commandBus->dispatch($addImage);

        return new NoContentResponse();
    }
}
