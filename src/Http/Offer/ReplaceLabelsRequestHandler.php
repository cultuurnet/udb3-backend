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
use CultuurNet\UDB3\Model\Serializer\ValueObject\Taxonomy\Label\LabelsDenormalizer;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use CultuurNet\UDB3\Offer\Commands\ReplaceLabels;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class ReplaceLabelsRequestHandler implements RequestHandlerInterface
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

        /** @var Labels $labels */
        $labels = RequestBodyParserFactory::createBaseParser(
            new JsonSchemaValidatingRequestBodyParser(
                // Deze validate moet nu ook werken met een lege lijst om alle labels te verwijderen.
                JsonSchemaLocator::getSchemaFileByOfferType(
                    $offerType,
                    JsonSchemaLocator::EVENT_LABELS_PUT,
                    JsonSchemaLocator::PLACE_LABELS_PUT
                )
            ),
            new DenormalizingRequestBodyParser(new LabelsDenormalizer(), Labels::class)
        )
            ->parse($request)
            ->getParsedBody();

        // Fire hier een nieuw ReplaceLabels commando
        $this->commandBus->dispatch(new ReplaceLabels($offerId, $labels));

        return new NoContentResponse();
    }
}
