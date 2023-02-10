<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security\Permission;

use CultuurNet\UDB3\Contributor\ContributorRepository;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddresses;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\UserEmailAddressRepository;
use CultuurNet\UDB3\StringLiteral;

final class ContributorVoter implements PermissionVoter
{
    private UserEmailAddressRepository $repository;

    private ContributorRepository $contributorRepository;

    public function __construct(
        UserEmailAddressRepository $repository,
        ContributorRepository $contributorRepository
    ) {
        $this->repository = $repository;
        $this->contributorRepository = $contributorRepository;
    }

    public function isAllowed(Permission $permission, StringLiteral $itemId, StringLiteral $userId): bool
    {
        $email = $this->repository->getEmailForUserId($userId->toNative());
        return $email && in_array($email, $this->getEmailAddressesForItem($itemId->toNative())->toArray());
    }

    private function getEmailAddressesForItem(string $itemId): EmailAddresses
    {
        return $this->contributorRepository->getContributors(new UUID($itemId));
    }
}
