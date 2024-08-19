<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\ReadModel\Relations\Doctrine;

use CultuurNet\UDB3\Place\ReadModel\Relations\PlaceRelationsRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement as DriverStatement;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;

final class DBALPlaceRelationsRepository implements PlaceRelationsRepository
{
    private string $tableName = 'place_relations';
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function storeRelations(string $placeId, ?string $organizerId): void
    {
        $insert = $this->prepareInsertStatement();
        $insert->bindValue('place', $placeId);
        $insert->bindValue('organizer', $organizerId);
        $insert->execute();
    }

    private function prepareInsertStatement(): DriverStatement
    {
        $table = $this->connection->quoteIdentifier($this->tableName);

        return $this->connection->prepare(
            "REPLACE INTO {$table}
             (place, organizer)
             VALUES (:place, :organizer)"
        );
    }

    public function getPlacesOrganizedByOrganizer(string $organizerId): array
    {
        $q = $this->connection->createQueryBuilder();
        $q
            ->select('place')
            ->from($this->tableName)
            ->where('organizer = ?')
            ->setParameter(0, $organizerId);

        $results = $q->execute();

        $places = [];
        while ($id = $results->fetchColumn(0)) {
            $places[] = $id;
        }

        return $places;
    }

    public function removeRelations(string $placeId): void
    {
        $q = $this->connection->createQueryBuilder();
        $q->delete($this->tableName)
            ->where('place = ?')
            ->setParameter(0, $placeId);

        $q->execute();
    }

    public function configureSchema(Schema $schema): ?Table
    {
        if ($schema->hasTable($this->tableName)) {
            return null;
        }

        return $this->configureTable();
    }

    public function configureTable(): ?Table
    {
        $schema = new Schema();

        $table = $schema->createTable($this->tableName);

        $table->addColumn(
            'place',
            'string',
            ['length' => 36, 'notnull' => false]
        );
        $table->addColumn(
            'organizer',
            'string',
            ['length' => 36, 'notnull' => false]
        );

        $table->setPrimaryKey(['place']);

        return $table;
    }
}
