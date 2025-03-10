<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\ReadModel\Search;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use Doctrine\DBAL\Connection;

class SearchByRoleIdAndPermissions
{
    protected Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function findAllUsers(Uuid $roleId, array $permissions) : array
    {
        $qb = $this->connection->createQueryBuilder();
        $q = $qb
            ->select('r.user_id')
            ->from('user_roles', 'r')
            ->leftJoin('r', 'role_permissions', 'p', 'r.role_id = p.role_id')
            ->where('r.role_id = :roleId')
            ->andWhere($qb->expr()->in('p.permission', ':permissions'))
            ->setParameter('roleId', $roleId->toString())
            ->setParameter('permissions', $permissions, Connection::PARAM_STR_ARRAY);

        return $q->execute()->fetchAllAssociative();
    }
}
