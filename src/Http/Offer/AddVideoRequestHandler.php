<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Http\Request\Body\DenormalizingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaLocator;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaValidatingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParserFactory;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\JsonLdResponse;
use CultuurNet\UDB3\Model\Serializer\ValueObject\MediaObject\VideoDenormalizer;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\Video;
use CultuurNet\UDB3\Offer\Commands\Video\AddVideo;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Ramsey\Uuid\UuidFactoryInterface;

final class AddVideoRequestHandler implements RequestHandlerInterface
{
    private CommandBus $commandBus;
    private RequestBodyParser $parser;

    public function __construct(CommandBus $commandBus, UuidFactoryInterface $uuidFactory)
    {
        $this->commandBus = $commandBus;
        $this->parser = RequestBodyParserFactory::createBaseParser(
            new JsonSchemaValidatingRequestBodyParser(JsonSchemaLocator::OFFER_VIDEOS_POST),
            new DenormalizingRequestBodyParser(new VideoDenormalizer($uuidFactory), Video::class)
        );
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $offerId = $routeParameters->getOfferId();

        /** @var Video $video */
        $video = $this->parser->parse($request)->getParsedBody();

        $this->commandBus->dispatch(new AddVideo(new UUID($offerId), $video));

        return new JsonLdResponse([
            'videoId' => $video->getId()->toString(),
        ]);
    }
}
