<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Organizer;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Organizer\Commands\DeleteDescription;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class DeleteDescriptionRequestHandler implements RequestHandlerInterface
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
        $language = $routeParameters->getLanguage();

        $this->commandBus->dispatch(new DeleteDescription($organizerId, $language));

        return new NoContentResponse();
    }
}
