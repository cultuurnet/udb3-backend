<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Http\Request\Body\DenormalizingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaLocator;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaValidatingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParserFactory;
use CultuurNet\UDB3\Http\Request\RequestHandler;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar\StatusDenormalizer;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Status;
use CultuurNet\UDB3\Offer\Commands\Status\UpdateStatus;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class UpdateStatusRequestHandler implements RequestHandler
{
    private CommandBus $commandBus;
    private RequestBodyParser $parser;

    public function __construct(CommandBus $commandBus)
    {
        $this->commandBus = $commandBus;
        $this->parser = RequestBodyParserFactory::createBaseParser(
            new JsonSchemaValidatingRequestBodyParser(JsonSchemaLocator::OFFER_STATUS),
            new DenormalizingRequestBodyParser(new StatusDenormalizer(), Status::class)
        );
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $offerId = $routeParameters->get('offerId');

        /** @var Status $status */
        $status = $this->parser->parse($request)->getParsedBody();

        $this->commandBus->dispatch(
            new UpdateStatus($offerId, \CultuurNet\UDB3\Event\ValueObjects\Status::fromUdb3ModelStatus($status))
        );

        return new NoContentResponse();
    }
}
