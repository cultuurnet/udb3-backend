<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Security\Permission;

use CultuurNet\UDB3\Role\ReadModel\Constraints\UserConstraintsReadRepositoryInterface;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Search\CountingSearchServiceInterface;
use CultuurNet\UDB3\Security\Permission\PermissionVoterInterface;
use ValueObjects\StringLiteral\StringLiteral;

class RoleConstraintVoter implements PermissionVoterInterface
{
    /**
     * @var UserConstraintsReadRepositoryInterface
     */
    private $userConstraintsReadRepository;

    /**
     * @var CountingSearchServiceInterface
     */
    private $searchService;

    public function __construct(
        UserConstraintsReadRepositoryInterface $userConstraintsReadRepository,
        CountingSearchServiceInterface $searchService
    ) {
        $this->userConstraintsReadRepository = $userConstraintsReadRepository;
        $this->searchService = $searchService;
    }

    public function isAllowed(
        Permission $permission,
        StringLiteral $itemId,
        StringLiteral $userId
    ): bool {
        $constraints = $this->userConstraintsReadRepository->getByUserAndPermission(
            $userId,
            $permission
        );
        if (count($constraints) < 1) {
            return false;
        }

        $query = $this->createQueryFromConstraints(
            $constraints,
            $itemId
        );

        $totalItems = $this->searchService->search($query);

        return $totalItems === 1;
    }

    private function createQueryString(
        StringLiteral $constraint,
        StringLiteral $offerId
    ): string {
        $constraintStr = '(' . $constraint->toNative() . ')';
        $offerIdStr = $offerId->toNative();

        return '(' . $constraintStr . ' AND id:' . $offerIdStr . ')';
    }

    private function createQueryFromConstraints(
        array $constraints,
        StringLiteral $offerId
    ): string {
        $queryString = '';

        foreach ($constraints as $constraint) {
            if (strlen($queryString)) {
                $queryString .= ' OR ';
            }

            $queryString .= $this->createQueryString($constraint, $offerId);
        }

        return $queryString;
    }
}
