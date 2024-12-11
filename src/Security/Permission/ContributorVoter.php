<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security\Permission;

use CultuurNet\UDB3\Contributor\ContributorRepository;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\UserEmailAddressRepository;

final class ContributorVoter implements PermissionVoter
{
    private UserEmailAddressRepository $userEmailAddressRepository;

    private ContributorRepository $contributorRepository;

    public function __construct(
        UserEmailAddressRepository $repository,
        ContributorRepository $contributorRepository
    ) {
        $this->userEmailAddressRepository = $repository;
        $this->contributorRepository = $contributorRepository;
    }

    public function isAllowed(Permission $permission, string $itemId, string $userId): bool
    {
        $email = $this->userEmailAddressRepository->getEmailForUserId($userId);
        return $email && $this->contributorRepository->isContributor(new Uuid($itemId), $email);
    }
}
