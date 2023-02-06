<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Contributor;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;

final class ContributorRepository implements ContributorRepositoryInterface
{
    private Connection $connection;

    private string $manageContributorsTableName;

    public function __construct(
        Connection $connection,
        string $manageContributorsTableName
    ) {
        $this->connection = $connection;
        $this->manageContributorsTableName = $manageContributorsTableName;
    }

    /**
     * @return EmailAddress[]
     */
    public function getContributors(UUID $id): array
    {
        $results = $this->connection->createQueryBuilder()
            ->select('email')
            ->from($this->manageContributorsTableName)
            ->where('uuid = :id')
            ->setParameter(':id', $id->toString())
            ->execute()
            ->fetchAll(FetchMode::COLUMN);

        return array_map(
            fn (string $email) => new EmailAddress($email),
            $results
        );
    }

    public function isContributor(UUID $id, EmailAddress $emailAddress): bool
    {
        $results = $this->connection->createQueryBuilder()
            ->select('email')
            ->from($this->manageContributorsTableName)
            ->where('uuid = :id')
            ->andWhere('email = :email')
            ->setParameter(':id', $id->toString())
            ->setParameter(':email', $emailAddress->toString())
            ->execute()
            ->fetchAll();

        return count($results) > 0;
    }

    public function addContributor(UUID $id, EmailAddress $emailAddress): void
    {
        $this->connection
            ->insert(
                $this->manageContributorsTableName,
                [
                    'uuid' => $id->toString(),
                    'email' => $emailAddress->toString(),
                ]
            );
    }

    public function deleteContributors(UUID $id): void
    {
        $this->connection
            ->delete(
                $this->manageContributorsTableName,
                ['uuid' => $id->toString()]
            );
    }
}
