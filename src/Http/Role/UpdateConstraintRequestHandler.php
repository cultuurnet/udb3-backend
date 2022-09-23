<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Role;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParserFactory;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Role\Commands\UpdateConstraint;
use CultuurNet\UDB3\Role\ValueObjects\Query;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

class UpdateConstraintRequestHandler implements RequestHandlerInterface
{
    private CommandBus $commandBus;

    public function __construct(CommandBus $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $roleId = $routeParameters->getRoleId();

        $requestBodyParser = RequestBodyParserFactory::createBaseParser(
            new QueryValidatingRequestBodyParser()
        );

        /** @var \stdClass $data */
        $data = $requestBodyParser->parse($request)->getParsedBody();

        $this->commandBus->dispatch(
            new UpdateConstraint(
                $roleId,
                new Query($data->query)
            )
        );

        return new Response(StatusCodeInterface::STATUS_NO_CONTENT);
    }
}
