<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\Place\ReadModel\Relations\Doctrine;

use CultuurNet\UDB3\Place\ReadModel\Relations\RepositoryInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;

class DBALRepository implements RepositoryInterface
{
    protected $tableName = 'place_relations';

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function storeRelations($placeId, $organizerId)
    {
        $this->connection->beginTransaction();

        $insert = $this->prepareInsertStatement();
        $insert->bindValue('place', $placeId);
        $insert->bindValue('organizer', $organizerId);
        $insert->execute();

        $this->connection->commit();
    }

    private function prepareInsertStatement()
    {
        $table = $this->connection->quoteIdentifier($this->tableName);
        
        return $this->connection->prepare(
            "REPLACE INTO {$table}
             (place, organizer)
             VALUES (:place, :organizer)"
        );
    }

    public function getPlacesOrganizedByOrganizer($organizerId)
    {
        $q = $this->connection->createQueryBuilder();
        $q
            ->select('place')
            ->from($this->tableName)
            ->where('organizer = ?')
            ->setParameter(0, $organizerId);

        $results = $q->execute();

        $places = array();
        while ($id = $results->fetchColumn(0)) {
            $places[] = $id;
        }

        return $places;
    }

    public function removeRelations($placeId)
    {
        $q = $this->connection->createQueryBuilder();
        $q->delete($this->tableName)
            ->where('place = ?')
            ->setParameter(0, $placeId);

        $q->execute();
    }

    /**
     * @return \Doctrine\DBAL\Schema\Table|null
     */
    public function configureSchema(Schema $schema)
    {
        if ($schema->hasTable($this->tableName)) {
            return null;
        }

        return $this->configureTable();
    }

    public function configureTable()
    {
        $schema = new Schema();

        $table = $schema->createTable($this->tableName);

        $table->addColumn(
            'place',
            'string',
            array('length' => 36, 'notnull' => false)
        );
        $table->addColumn(
            'organizer',
            'string',
            array('length' => 36, 'notnull' => false)
        );

        $table->setPrimaryKey(array('place'));

        return $table;
    }
}
