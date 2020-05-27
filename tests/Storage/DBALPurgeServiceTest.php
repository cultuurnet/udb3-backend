<?php

namespace CultuurNet\UDB3\Storage;

use CultuurNet\UDB3\DBALTestConnectionTrait;
use Doctrine\DBAL\Schema\Schema;
use PHPUnit\Framework\TestCase;

class DBALPurgeServiceTest extends TestCase
{
    const PERSON = 'person';
    const ID = 'id';
    const FIRST_NAME = 'firstName';
    const LAST_NAME = 'lastName';

    use DBALTestConnectionTrait;

    /**
     * @var DBALPurgeService
     */
    private $dbalPurgeService;

    protected function setUp()
    {
        $this->createPersonTable();

        $this->insertPersons();

        $this->dbalPurgeService = new DBALPurgeService(
            $this->getConnection(),
            self::PERSON
        );
    }

    /**
     * @test
     */
    public function it_can_purge_table_data_and_reset_the_sequence()
    {
        $personCount = $this->getPersonCount();
        $this->assertEquals(2, $personCount);

        $sequence = $this->getSequence();
        $this->assertEquals(3, $sequence);

        $this->dbalPurgeService->purgeAll();

        $personCount = $this->getPersonCount();
        $this->assertEquals(0, $personCount);

        $sequence = $this->getSequence();
        $this->assertEquals(1, $sequence);
    }

    private function createPersonTable()
    {
        $schema = new Schema();

        $personTable = $schema->createTable(self::PERSON);

        $personTable->addColumn(self::ID, 'integer', array('autoincrement' => true));
        $personTable->addColumn(self::FIRST_NAME, 'string', array('length' => 256));
        $personTable->addColumn(self::LAST_NAME, 'string', array('length' => 256));
        $personTable->setPrimaryKey(array(self::ID));

        $platform = $this->getConnection()->getDatabasePlatform();
        $queries = $schema->toSql($platform);
        foreach ($queries as $query) {
            $this->getConnection()->executeQuery($query);
        }
    }

    private function insertPersons()
    {
        $this->insertPerson('firstName1', 'lastName1');
        $this->insertPerson('firstName2', 'lastName2');
    }

    /**
     * @param string $firstName
     * @param string $lastName
     */
    private function insertPerson($firstName, $lastName)
    {
        $queryBuilder = $this->getConnection()->createQueryBuilder();

        $queryBuilder->insert(self::PERSON)
            ->setValue(self::FIRST_NAME, '?')
            ->setValue(self::LAST_NAME, '?')
            ->setParameter(0, $firstName)
            ->setParameter(1, $lastName)
            ->execute();
    }

    /**
     * @return int
     */
    private function getPersonCount()
    {
        $queryBuilder = $this->getConnection()->createQueryBuilder();

        $queryBuilder
            ->select(self::ID)
            ->from(self::PERSON);

        $persons = $queryBuilder->execute()->fetchAll();

        return count($persons);
    }

    /**
     * @return int
     */
    private function getSequence()
    {
        $this->insertPerson('test', 'test');
        return $this->getConnection()->lastInsertId();
    }
}
