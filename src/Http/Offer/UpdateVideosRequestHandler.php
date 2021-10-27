<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Http\Request\Body\DenormalizingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaLocator;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaValidatingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParserFactory;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Offer\Commands\Video\UpdateVideoDenormalizer;
use CultuurNet\UDB3\Offer\Commands\Video\UpdateVideos;
use CultuurNet\UDB3\Offer\Commands\Video\UpdateVideosDenormalizer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class UpdateVideosRequestHandler implements RequestHandlerInterface
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
                    JsonSchemaLocator::EVENT_VIDEOS_PATCH,
                    JsonSchemaLocator::PLACE_VIDEOS_PATCH
                )
            ),
            new DenormalizingRequestBodyParser(
                new UpdateVideosDenormalizer(
                    new UpdateVideoDenormalizer($offerId)
                ),
                UpdateVideos::class
            )
        );

        /** @var UpdateVideos $updateVideos */
        $updateVideos = $parser->parse($request)->getParsedBody();

        foreach ($updateVideos as $updateVideo) {
            $this->commandBus->dispatch($updateVideo);
        }

        return new NoContentResponse();
    }
}
