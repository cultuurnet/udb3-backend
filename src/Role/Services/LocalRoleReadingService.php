<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Services;

use Broadway\Repository\Repository;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\LocalEntityService;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

class LocalRoleReadingService extends LocalEntityService implements RoleReadingServiceInterface
{
    /**
     * @var DocumentRepository
     */
    private $roleLabelsReadRepository;

    /**
     * @var DocumentRepository
     */
    private $roleUsersReadRepository;

    /**
     * @var DocumentRepository
     */
    private $userRolesReadRepository;

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

    /**
     * @inheritdoc
     */
    public function getLabelsByRoleUuid(UUID $uuid)
    {
        return $this->roleLabelsReadRepository->get($uuid->toNative());
    }

    /**
     * @inheritdoc
     */
    public function getUsersByRoleUuid(UUID $uuid)
    {
        return $this->roleUsersReadRepository->get($uuid->toNative());
    }

    /**
     * @inheritdoc
     */
    public function getRolesByUserId(StringLiteral $userId)
    {
        return $this->userRolesReadRepository->get($userId->toNative());
    }
}
