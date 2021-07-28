<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Security\Permission;

use CultuurNet\UDB3\Offer\Security\Sapi3SearchQueryFactory;
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
     * @var Sapi3SearchQueryFactory
     */
    private $searchQueryFactory;

    /**
     * @var CountingSearchServiceInterface
     */
    private $searchService;

    public function __construct(
        UserConstraintsReadRepositoryInterface $userConstraintsReadRepository,
        Sapi3SearchQueryFactory $searchQueryFactory,
        CountingSearchServiceInterface $searchService
    ) {
        $this->userConstraintsReadRepository = $userConstraintsReadRepository;
        $this->searchQueryFactory = $searchQueryFactory;
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

        $query = $this->searchQueryFactory->createFromConstraints(
            $constraints,
            $itemId
        );

        $totalItems = $this->searchService->search($query);

        return $totalItems === 1;
    }
}
