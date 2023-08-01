<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Offer\CommandHandlers\DeleteDescriptionHandler;
use CultuurNet\UDB3\Offer\Commands\DeleteDescription;
use League\Container\Container;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class DeleteDescriptionRequestHandler implements RequestHandlerInterface
{
    private CommandBus $commandBus;

    public function __construct(CommandBus $commandBus, Container $container)
    {
        $this->commandBus = $commandBus;
        $this->container = $container;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);

        $event = new DeleteDescription(
            $routeParameters->getOfferId(),
            $routeParameters->getOfferType(),
            $routeParameters->getLanguage()
        );

        $this->commandBus->dispatch($event);

        $this->container->get(DeleteDescriptionHandler::class)->handle($event);

        return new NoContentResponse();
    }
}
