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
use CultuurNet\UDB3\Offer\OfferType;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Ramsey\Uuid\UuidFactoryInterface;
use RuntimeException;

final class AddVideoRequestHandler implements RequestHandlerInterface
{
    private CommandBus $commandBus;
    private UuidFactoryInterface $uuidFactory;

    public function __construct(
        CommandBus $commandBus,
        UuidFactoryInterface $uuidFactory
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
            new JsonSchemaValidatingRequestBodyParser($this->getSchemaLocation($offerType)),
            new DenormalizingRequestBodyParser(new VideoDenormalizer($this->uuidFactory), Video::class)
        );

        /** @var Video $video */
        $video = $parser->parse($request)->getParsedBody();

        $this->commandBus->dispatch(new AddVideo($offerId, $video));

        return new JsonLdResponse([
            'videoId' => $video->getId(),
        ]);
    }

    private function getSchemaLocation(OfferType $offerType): string
    {
        if ($offerType->sameValueAs(OfferType::EVENT())) {
            return JsonSchemaLocator::EVENT_VIDEOS_POST;
        }
        if ($offerType->sameValueAs(OfferType::PLACE())) {
            return JsonSchemaLocator::PLACE_VIDEOS_POST;
        }
        throw new RuntimeException('No schema found for unknown offer type ' . $offerType->toNative());
    }
}
