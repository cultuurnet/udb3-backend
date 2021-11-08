<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Services;

use Broadway\Repository\Repository;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\LocalEntityService;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

class LocalRoleReadingService extends LocalEntityService implements RoleReadingServiceInterface
{
    private DocumentRepository $roleLabelsReadRepository;

    private DocumentRepository $roleUsersReadRepository;

    private DocumentRepository $userRolesReadRepository;

    public function __construct(
        DocumentRepository $roleReadRepository,
        Repository $roleWriteRepository,
        IriGeneratorInterface $iriGenerator,
        DocumentRepository $roleLabelsReadRepository,
        DocumentRepository $roleUsersReadRepository,
        DocumentRepository $userRolesReadRepository
    ) {
        parent::__construct(
            $roleReadRepository,
            $roleWriteRepository,
            $iriGenerator
        );

        $this->roleLabelsReadRepository = $roleLabelsReadRepository;
        $this->roleUsersReadRepository = $roleUsersReadRepository;
        $this->userRolesReadRepository = $userRolesReadRepository;
    }

    public function getLabelsByRoleUuid(UUID $uuid): JsonDocument
    {
        return $this->roleLabelsReadRepository->fetch($uuid->toNative());
    }

    public function getUsersByRoleUuid(UUID $uuid): JsonDocument
    {
        return $this->roleUsersReadRepository->fetch($uuid->toNative());
    }

    public function getRolesByUserId(StringLiteral $userId): JsonDocument
    {
        return $this->userRolesReadRepository->fetch($userId->toNative());
    }
}
