<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Role;

use Broadway\CommandHandling\CommandBus;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Role\Commands\CreateRole;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class CreateRoleRequestHandler implements RequestHandlerInterface
{
    private CommandBus $commandBus;

    private UuidGeneratorInterface $uuidGenerator;

    public function __construct(CommandBus $commandBus, UuidGeneratorInterface $uuidGenerator)
    {
        $this->commandBus = $commandBus;
        $this->uuidGenerator = $uuidGenerator;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = Json::decodeAssociatively($request->getBody()->getContents());

        if (empty($body['name'])) {
            throw ApiProblem::requiredFieldMissing('name');
        }

        $roleId = new UUID($this->uuidGenerator->generate());
        $this->commandBus->dispatch(new CreateRole($roleId, (string) $body['name']));

        return new JsonResponse(['roleId' => $roleId->toString()], StatusCodeInterface::STATUS_CREATED);
    }
}
