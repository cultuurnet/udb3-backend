<?php

namespace CultuurNet\UDB3\Role\Services;

use Broadway\Repository\RepositoryInterface;
use CultuurNet\UDB3\Event\ReadModel\DocumentRepositoryInterface;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\LocalEntityService;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

class LocalRoleReadingService extends LocalEntityService implements RoleReadingServiceInterface
{
    /**
     * @var DocumentRepositoryInterface
     */
    private $roleLabelsReadRepository;

    /**
     * @var DocumentRepositoryInterface
     */
    private $roleUsersReadRepository;

    /**
     * @var DocumentRepositoryInterface
     */
    private $userRolesReadRepository;

    /**
     * ReadRoleRestController constructor.
     * @param DocumentRepositoryInterface $roleReadRepository
     * @param RepositoryInterface $roleWriteRepository
     * @param IriGeneratorInterface $iriGenerator
     * @param DocumentRepositoryInterface $roleLabelsReadRepository
     * @param DocumentRepositoryInterface $roleUsersReadRepository
     * @param DocumentRepositoryInterface $userRolesReadRepository
     */
    public function __construct(
        DocumentRepositoryInterface $roleReadRepository,
        RepositoryInterface $roleWriteRepository,
        IriGeneratorInterface $iriGenerator,
        DocumentRepositoryInterface $roleLabelsReadRepository,
        DocumentRepositoryInterface $roleUsersReadRepository,
        DocumentRepositoryInterface $userRolesReadRepository
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
