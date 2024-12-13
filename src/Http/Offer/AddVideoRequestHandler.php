<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Http\Request\Body\DenormalizingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaLocator;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaValidatingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParserFactory;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\JsonLdResponse;
use CultuurNet\UDB3\Model\Serializer\ValueObject\MediaObject\VideoDenormalizer;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\Video;
use CultuurNet\UDB3\Offer\Commands\Video\AddVideo;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use CultuurNet\UDB3\Model\ValueObject\Identity\UuidFactory\UuidFactory;

final class AddVideoRequestHandler implements RequestHandlerInterface
{
    private CommandBus $commandBus;
    private UuidFactory $uuidFactory;

    public function __construct(
        CommandBus $commandBus,
        UuidFactory $uuidFactory
    ) {
        $this->commandBus = $commandBus;
        $this->uuidFactory = $uuidFactory;
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
                    JsonSchemaLocator::EVENT_VIDEOS_POST,
                    JsonSchemaLocator::PLACE_VIDEOS_POST
                )
            ),
            new DenormalizingRequestBodyParser(new VideoDenormalizer($this->uuidFactory), Video::class)
        );

        /** @var Video $video */
        $video = $parser->parse($request)->getParsedBody();

        $this->commandBus->dispatch(new AddVideo($offerId, $video));

        return new JsonLdResponse([
            'videoId' => $video->getId(),
        ]);
    }
}
