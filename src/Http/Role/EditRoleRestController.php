<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Role;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Deserializer\DeserializerInterface;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Label\Services\ReadServiceInterface;
use CultuurNet\UDB3\Role\Commands\UpdateRoleRequestDeserializer;
use CultuurNet\UDB3\Role\Services\RoleEditingServiceInterface;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\HttpFoundation\Response\NoContent;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use ValueObjects\Exception\InvalidNativeArgumentException;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

class EditRoleRestController
{
    private RoleEditingServiceInterface $service;

    private CommandBus $commandBus;

    private UpdateRoleRequestDeserializer $updateRoleRequestDeserializer;

    private ReadServiceInterface $labelEntityService;

    private DeserializerInterface $queryJsonDeserializer;

    public function __construct(
        RoleEditingServiceInterface $service,
        CommandBus $commandBus,
        UpdateRoleRequestDeserializer $updateRoleRequestDeserializer,
        ReadServiceInterface $labelEntityService,
        DeserializerInterface $queryJsonDeserializer
    ) {
        $this->service = $service;
        $this->commandBus = $commandBus;
        $this->updateRoleRequestDeserializer = $updateRoleRequestDeserializer;
        $this->labelEntityService = $labelEntityService;
        $this->queryJsonDeserializer = $queryJsonDeserializer;
    }

    public function create(Request $request): JsonResponse
    {
        $bodyContent = json_decode($request->getContent());
        if (empty($bodyContent->name)) {
            throw new \InvalidArgumentException('Required fields are missing');
        }

        $roleId = $this->service->create(
            new StringLiteral($bodyContent->name)
        );

        return new JsonResponse(['roleId' => $roleId->toNative()], 201);
    }

    public function update(Request $request, string $id): Response
    {
        $command = $this->updateRoleRequestDeserializer->deserialize($request, $id);

        $this->commandBus->dispatch($command);

        return new NoContent();
    }

    public function addConstraint(Request $request, string $id): Response
    {
        $query = $this->queryJsonDeserializer->deserialize(
            new StringLiteral($request->getContent())
        );

        $this->service->addConstraint(new UUID($id), $query);

        return new NoContent();
    }

    public function updateConstraint(Request $request, string $id): Response
    {
        $query = $this->queryJsonDeserializer->deserialize(
            new StringLiteral($request->getContent())
        );

        $this->service->updateConstraint(new UUID($id), $query);

        return new NoContent();
    }

    public function removeConstraint(string $id): Response
    {
        $this->service->removeConstraint(new UUID($id));

        return new NoContent();
    }

    public function delete(string $id): Response
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Required field id is missing');
        }

        $this->service->delete(new UUID($id));

        return new NoContent();
    }

    public function addPermission(string $roleId, string $permissionKey): Response
    {
        if (empty($roleId)) {
            throw new InvalidArgumentException('Required field roleId is missing');
        }

        if (!in_array($permissionKey, array_keys(Permission::getConstants()))) {
            throw new InvalidArgumentException('Field permission is invalid.');
        }

        $this->service->addPermission(
            new UUID($roleId),
            Permission::getByName($permissionKey)
        );

        return new NoContent();
    }

    public function removePermission(string $roleId, string $permissionKey): Response
    {
        if (empty($roleId)) {
            throw new InvalidArgumentException('Required field roleId is missing');
        }

        if (!in_array($permissionKey, array_keys(Permission::getConstants()))) {
            throw new InvalidArgumentException('Field permission is invalid.');
        }

        $this->service->removePermission(
            new UUID($roleId),
            Permission::getByName($permissionKey)
        );

        return new NoContent();
    }

    public function addLabel(string $roleId, string $labelIdentifier): Response
    {
        $labelId = $this->getLabelId($labelIdentifier);

        if (is_null($labelId)) {
            throw ApiProblem::blank('There is no label with identifier: ' . $labelIdentifier, 404);
        }

        try {
            $roleId = new UUID($roleId);
        } catch (InvalidNativeArgumentException $e) {
            throw new InvalidArgumentException('Required field roleId is not a valid uuid.');
        }

        $this->service->addLabel($roleId, $labelId);

        return new NoContent();
    }

    public function removeLabel(string $roleId, string $labelIdentifier): Response
    {
        $labelId = $this->getLabelId($labelIdentifier);

        if (is_null($labelId)) {
            throw ApiProblem::blank('There is no label with identifier: ' . $labelIdentifier, 404);
        }

        try {
            $roleId = new UUID($roleId);
        } catch (InvalidNativeArgumentException $e) {
            throw new InvalidArgumentException('Required field roleId is not a valid uuid.');
        }

        $this->service->removeLabel($roleId, $labelId);

        return new NoContent();
    }

    public function addUser(string $roleId, string $userId): Response
    {
        try {
            $roleId = new UUID($roleId);
        } catch (InvalidNativeArgumentException $e) {
            throw new InvalidArgumentException('Required field roleId is not a valid uuid.');
        }

        if (empty($userId)) {
            throw new InvalidArgumentException('Required field userId is missing');
        }

        $userId = new StringLiteral($userId);

        $this->service->addUser($roleId, $userId);

        return new NoContent();
    }

    public function removeUser(string $roleId, string $userId): Response
    {
        try {
            $roleId = new UUID($roleId);
        } catch (InvalidNativeArgumentException $e) {
            throw new InvalidArgumentException('Required field roleId is not a valid uuid.');
        }

        if (empty($userId)) {
            throw new InvalidArgumentException('Required field userId is missing');
        }

        $userId = new StringLiteral($userId);

        $this->service->removeUser($roleId, $userId);

        return new NoContent();
    }

    private function getLabelId(string $labelIdentifier): ?UUID
    {
        try {
            return new UUID($labelIdentifier);
        } catch (InvalidNativeArgumentException $exception) {
            $entity = $this->labelEntityService->getByName(
                new StringLiteral($labelIdentifier)
            );

            return is_null($entity) ? null : $entity->getUuid();
        }
    }
}
