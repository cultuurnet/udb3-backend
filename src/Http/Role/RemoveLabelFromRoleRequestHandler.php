<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Role;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Role\Commands\RemoveLabel;
use Fig\Http\Message\StatusCodeInterface;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

class RemoveLabelFromRoleRequestHandler implements RequestHandlerInterface
{
    private CommandBus $commandBus;

    private ReadRepositoryInterface $labelRepository;

    public function __construct(CommandBus $commandBus, ReadRepositoryInterface $labelRepository)
    {
        $this->commandBus = $commandBus;
        $this->labelRepository = $labelRepository;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $roleId = $routeParameters->getRoleId();

        $labelIdentifier = $routeParameters->get('labelIdentifier');
        try {
            $labelId = new UUID($labelIdentifier);
        } catch (InvalidArgumentException $exception) {
            $entity = $this->labelRepository->getByName($labelIdentifier);

            if ($entity === null) {
                throw ApiProblem::blank('There is no label with identifier: ' . $labelIdentifier, 404);
            }

            $labelId = new UUID($entity->getUuid()->toString());
        }

        $this->commandBus->dispatch(new RemoveLabel($roleId, $labelId));

        return new Response(StatusCodeInterface::STATUS_NO_CONTENT);
    }
}
