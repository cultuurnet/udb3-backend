<?php

namespace CultuurNet\UDB3\Symfony\Role;

use Broadway\CommandHandling\CommandBusInterface;
use Crell\ApiProblem\ApiProblem;
use CultuurNet\Deserializer\DeserializerInterface;
use CultuurNet\UDB3\Label\Services\ReadServiceInterface;
use CultuurNet\UDB3\Role\Commands\UpdateRoleRequestDeserializer;
use CultuurNet\UDB3\Role\Services\RoleEditingServiceInterface;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Symfony\HttpFoundation\ApiProblemJsonResponse;
use CultuurNet\UDB3\Symfony\HttpFoundation\NoContent;
use CultuurNet\UDB3\ValueObject\SapiVersion;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use ValueObjects\Exception\InvalidNativeArgumentException;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

class EditRoleRestController
{
    /**
     * @var RoleEditingServiceInterface
     */
    private $service;

    /**
     * @var CommandBusInterface
     */
    private $commandBus;

    /**
     * @var UpdateRoleRequestDeserializer
     */
    private $updateRoleRequestDeserializer;

    /**
     * @var ReadServiceInterface
     */
    private $labelEntityService;

    /**
     * @var DeserializerInterface
     */
    private $queryJsonDeserializer;

    /**
     * EditRoleRestController constructor.
     * @param RoleEditingServiceInterface $service
     * @param CommandBusInterface $commandBus
     * @param UpdateRoleRequestDeserializer $updateRoleRequestDeserializer
     * @param ReadServiceInterface $labelEntityService
     * @param DeserializerInterface $queryJsonDeserializer
     */
    public function __construct(
        RoleEditingServiceInterface $service,
        CommandBusInterface $commandBus,
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

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function create(Request $request)
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

    /**
     * @param Request $request
     * @param $id
     * @return Response
     */
    public function update(Request $request, $id): Response
    {
        $command = $this->updateRoleRequestDeserializer->deserialize($request, $id);

        $this->commandBus->dispatch($command);

        return new NoContent();
    }

    /**
     * @param Request $request
     * @param string $id
     * @param string $sapiVersion
     * @return Response
     */
    public function addConstraint(Request $request, string $id, string $sapiVersion): Response
    {
        $query = $this->queryJsonDeserializer->deserialize(
            new StringLiteral($request->getContent())
        );

        $this->service->addConstraint(
            new UUID($id),
            SapiVersion::fromNative($sapiVersion),
            $query
        );

        return new NoContent();
    }

    /**
     * @param Request $request
     * @param string $id
     * @param string $sapiVersion
     * @return JsonResponse
     */
    public function updateConstraint(Request $request, string $id, string $sapiVersion): Response
    {
        $query = $this->queryJsonDeserializer->deserialize(
            new StringLiteral($request->getContent())
        );

        $this->service->updateConstraint(
            new UUID($id),
            SapiVersion::fromNative($sapiVersion),
            $query
        );

        return new NoContent();
    }

    /**
     * @param string $id
     * @param string $sapiVersion
     * @return Response
     */
    public function removeConstraint(string $id, string $sapiVersion): Response
    {
        $this->service->removeConstraint(
            new UUID($id),
            SapiVersion::fromNative($sapiVersion)
        );

        return new NoContent();
    }

    /**
     * @param $id
     * @return Response
     */
    public function delete($id): Response
    {
        $roleId = (string) $id;

        if (empty($roleId)) {
            throw new InvalidArgumentException('Required field roleId is missing');
        }

        $this->service->delete(new UUID($roleId));

        return new NoContent();
    }

    /**
     * @param $roleId
     * @param string $permissionKey
     * @return Response
     */
    public function addPermission($roleId, $permissionKey): Response
    {
        $roleId = (string) $roleId;

        if (empty($roleId)) {
            throw new InvalidArgumentException('Required field roleId is missing');
        }

        $permissionKey = (string) $permissionKey;

        if (!in_array($permissionKey, array_keys(Permission::getConstants()))) {
            throw new InvalidArgumentException('Field permission is invalid.');
        }

        $this->service->addPermission(
            new UUID($roleId),
            Permission::getByName($permissionKey)
        );

        return new NoContent();
    }

    /**
     * @param $roleId
     * @param string $permissionKey
     * @return Response
     */
    public function removePermission($roleId, $permissionKey): Response
    {
        $roleId = (string) $roleId;

        if (empty($roleId)) {
            throw new InvalidArgumentException('Required field roleId is missing');
        }

        $permissionKey = (string) $permissionKey;

        if (!in_array($permissionKey, array_keys(Permission::getConstants()))) {
            throw new InvalidArgumentException('Field permission is invalid.');
        }

        $this->service->removePermission(
            new UUID($roleId),
            Permission::getByName($permissionKey)
        );

        return new NoContent();
    }

    /**
     * @param string $roleId
     * @param string $labelIdentifier
     * @return Response
     * @throws InvalidArgumentException
     */
    public function addLabel($roleId, $labelIdentifier): Response
    {
        $roleId = (string) $roleId;
        $labelId = $this->getLabelId($labelIdentifier);

        if (is_null($labelId)) {
            $apiProblem = new ApiProblem('There is no label with identifier: ' . $labelIdentifier);
            $apiProblem->setStatus(Response::HTTP_NOT_FOUND);
            return new ApiProblemJsonResponse($apiProblem);
        }

        try {
            $roleId = new UUID($roleId);
        } catch (InvalidNativeArgumentException $e) {
            throw new InvalidArgumentException('Required field roleId is not a valid uuid.');
        }

        $this->service->addLabel($roleId, $labelId);

        return new NoContent();
    }

    /**
     * @param string $roleId
     * @param string $labelIdentifier
     * @return Response
     * @throws InvalidArgumentException
     */
    public function removeLabel($roleId, $labelIdentifier): Response
    {
        $roleId = (string) $roleId;
        $labelId = $this->getLabelId($labelIdentifier);

        if (is_null($labelId)) {
            $apiProblem = new ApiProblem('There is no label with identifier: ' . $labelIdentifier);
            $apiProblem->setStatus(Response::HTTP_NOT_FOUND);
            return new ApiProblemJsonResponse($apiProblem);
        }

        try {
            $roleId = new UUID($roleId);
        } catch (InvalidNativeArgumentException $e) {
            throw new InvalidArgumentException('Required field roleId is not a valid uuid.');
        }

        $this->service->removeLabel($roleId, $labelId);

        return new NoContent();
    }

    /**
     * @param $roleId
     * @param $userId
     * @return Response
     * @throws InvalidArgumentException
     */
    public function addUser($roleId, $userId): Response
    {
        $roleId = (string) $roleId;
        $userId = (string) $userId;

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

    /**
     * @param $roleId
     * @param $userId
     * @return Response
     * @throws InvalidArgumentException
     */
    public function removeUser($roleId, $userId): Response
    {
        $roleId = (string) $roleId;
        $userId = (string) $userId;

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

    /**
     * @param string $labelIdentifier
     * @return UUID|null
     */
    private function getLabelId($labelIdentifier)
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
