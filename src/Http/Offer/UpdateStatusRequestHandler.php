<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Event\ValueObjects\Status as LegacyStatus;
use CultuurNet\UDB3\Http\Request\Body\DenormalizingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaLocator;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaValidatingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParserFactory;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar\StatusDenormalizer;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Status;
use CultuurNet\UDB3\Offer\Commands\Status\UpdateStatus;
use CultuurNet\UDB3\Offer\OfferType;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

class UpdateStatusRequestHandler implements RequestHandlerInterface
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
            new JsonSchemaValidatingRequestBodyParser($this->getSchemaLocation($offerType)),
            new DenormalizingRequestBodyParser(new StatusDenormalizer(), Status::class)
        );

        /** @var Status $status */
        $status = $parser->parse($request)->getParsedBody();

        $this->commandBus->dispatch(
            new UpdateStatus($offerId, LegacyStatus::fromUdb3ModelStatus($status))
        );

        return new NoContentResponse();
    }

    private function getSchemaLocation(OfferType $offerType): string
    {
        if ($offerType->sameValueAs(OfferType::EVENT())) {
            return JsonSchemaLocator::EVENT_STATUS;
        }
        if ($offerType->sameValueAs(OfferType::PLACE())) {
            return JsonSchemaLocator::PLACE_STATUS;
        }
        throw new RuntimeException('No schema found for unknown offer type ' . $offerType->toNative());
    }
}
