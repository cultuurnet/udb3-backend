<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Contributor;

use CultuurNet\UDB3\Model\ValueObject\Identity\ItemType;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddresses;
use Doctrine\DBAL\Connection;

final class DbalContributorRepository implements ContributorRepository
{
    private Connection $connection;

    private const TABLE = 'contributor_relations';

    public function __construct(
        Connection $connection
    ) {
        $this->connection = $connection;
    }

    public function getContributors(UUID $id): EmailAddresses
    {
        $results = $this->connection->createQueryBuilder()
            ->select('email')
            ->from(self::TABLE)
            ->where('uuid = :id')
            ->setParameter(':id', $id->toString())
            ->execute()
            ->fetchFirstColumn();

        return EmailAddresses::fromArray(
            array_map(
                fn (string $email) => new EmailAddress($email),
                $results
            )
        );
    }

    public function isContributor(UUID $id, EmailAddress $emailAddress): bool
    {
        $results = $this->connection->createQueryBuilder()
            ->select('email')
            ->from(self::TABLE)
            ->where('uuid = :id')
            ->andWhere('email = :email')
            ->setParameter(':id', $id->toString())
            ->setParameter(':email', $emailAddress->toString())
            ->execute()
            ->fetchAllAssociative();

        return count($results) > 0;
    }

    public function updateContributors(UUID $id, EmailAddresses $emailAddresses, ItemType $itemType): void
    {
        $this->connection->transactional(
            function (Connection $connection) use ($id, $emailAddresses, $itemType): void {
                $connection
                    ->delete(
                        self::TABLE,
                        ['uuid' => $id->toString()]
                    );
                $emailsAsArray = $emailAddresses->toArray();
                foreach ($emailsAsArray as $email) {
                    $connection
                        ->insert(
                            self::TABLE,
                            [
                                'uuid' => $id->toString(),
                                'email' => $email->toString(),
                                'type' => $itemType->toString(),
                            ]
                        );
                }
            }
        );
    }
}
