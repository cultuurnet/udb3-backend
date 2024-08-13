<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ReadModel\Relations\Doctrine;

use CultuurNet\UDB3\DBALTestConnectionTrait;
use PHPUnit\Framework\TestCase;

class DBALRepositoryTest extends TestCase
{
    use DBALTestConnectionTrait;

    private DBALEventRelationsRepository $repository;

    private string $tableName;

    public function setUp(): void
    {
        $this->setUpDatabase();
        $this->repository = new DBALEventRelationsRepository(
            $this->getConnection()
        );

        $this->tableName = 'event_relations';
    }

    private function assertTableData(array $expectedData, string $tableName): void
    {
        $expectedData = array_values($expectedData);

        $results = $this->getConnection()->executeQuery('SELECT * from ' . $tableName);

        $actualData = $results->fetchAllAssociative();

        $this->assertEquals(
            $expectedData,
            $actualData
        );
    }

    private function insertTableData(string $tableName, array $rows): void
    {
        $q = $this->getConnection()->createQueryBuilder();

        $schema = $this->getConnection()->getSchemaManager()->createSchema();

        $columns = $schema
            ->getTable($tableName)
            ->getColumns();

        $values = [];
        foreach ($columns as $column) {
            $values[$column->getName()] = '?';
        }

        $q->insert($tableName)
            ->values($values);

        foreach ($rows as $row) {
            $parameters = [];
            foreach (array_keys($values) as $columnName) {
                $parameters[] = $row->$columnName;
            }

            $q->setParameters($parameters);

            $q->execute();
        }
    }

    /**
     * @test
     */
    public function it_updates_the_organizer_linked_to_an_event_when_a_relation_already_exists(): void
    {
        $existingData[] = (object)[
            'event' => 'event-id',
            'organizer' => 'old-organizer-id',
            'place' => 'some-place-id',
        ];
        $this->insertTableData($this->tableName, $existingData);
        $eventId = 'event-id';
        $organizerId = 'new-organizer-id';
        $expectedData[] = [
            'event' => 'event-id',
            'organizer' => 'new-organizer-id',
            'place' => 'some-place-id',
        ];

        $this->repository->storeOrganizer($eventId, $organizerId);

        $this->assertTableData($expectedData, $this->tableName);
    }

    /**
     * @test
     */
    public function it_creates_a_new_organizer_relation_when_an_event_has_no_existing_relations(): void
    {
        $eventId = 'event-id';
        $organizerId = 'organizer-id';
        $expectedData[] = [
            'event' => 'event-id',
            'organizer' => 'organizer-id',
            'place' => null,
        ];

        $this->repository->storeOrganizer($eventId, $organizerId);

        $this->assertTableData($expectedData, $this->tableName);
    }

    /**
     * @test
     */
    public function it_updates_existing_relations_when_removing_an_event_organizer(): void
    {
        $existingData[] = (object)[
            'event' => 'event-id',
            'organizer' => 'organizer-id',
            'place' => 'some-place-id',
        ];
        $this->insertTableData($this->tableName, $existingData);
        $eventId = 'event-id';
        $expectedData[] = [
            'event' => 'event-id',
            'organizer' => null,
            'place' => 'some-place-id',
        ];

        $this->repository->removeOrganizer($eventId);

        $this->assertTableData($expectedData, $this->tableName);
    }

    /**
     * @test
     */
    public function it_should_remove_all_relations_for_a_given_event(): void
    {
        $existingData = [
            (object)[
                'event' => 'e201cea1-4a79-4834-9501-b28a92900fa1',
                'organizer' => '3a4abf90-1859-49de-a667-b713c81aad28',
                'place' => 'e64362f5-43e1-468b-97d6-8981fb0fe426',
            ],
            (object)[
                'event' => 'cd996276-7aac-40b7-8bf4-e505dbbf11bf',
                'organizer' => '3a4abf90-1859-49de-a667-b713c81aad28',
                'place' => 'e64362f5-43e1-468b-97d6-8981fb0fe426',
            ],
        ];
        $this->insertTableData($this->tableName, $existingData);
        $eventId = 'e201cea1-4a79-4834-9501-b28a92900fa1';
        $expectedData[] = [
            'event' => 'cd996276-7aac-40b7-8bf4-e505dbbf11bf',
            'organizer' => '3a4abf90-1859-49de-a667-b713c81aad28',
            'place' => 'e64362f5-43e1-468b-97d6-8981fb0fe426',
        ];

        $this->repository->removeRelations($eventId);

        $this->assertTableData($expectedData, $this->tableName);
    }

    /**
     * @test
     */
    public function it_should_get_all_events_located_at_place(): void
    {
        $existingData = [
            (object)[
                'event' => 'e201cea1-4a79-4834-9501-b28a92900fa1',
                'organizer' => '3a4abf90-1859-49de-a667-b713c81aad28',
                'place' => 'e64362f5-43e1-468b-97d6-8981fb0fe426',
            ],
            (object)[
                'event' => 'cd996276-7aac-40b7-8bf4-e505dbbf11bf',
                'organizer' => '3a4abf90-1859-49de-a667-b713c81aad28',
                'place' => 'e64362f5-43e1-468b-97d6-8981fb0fe426',
            ],
        ];
        $this->insertTableData($this->tableName, $existingData);

        $events = $this->repository
            ->getEventsLocatedAtPlace('e64362f5-43e1-468b-97d6-8981fb0fe426');
        $expectedData = ['e201cea1-4a79-4834-9501-b28a92900fa1', 'cd996276-7aac-40b7-8bf4-e505dbbf11bf'];
        $this->assertEqualsCanonicalizing($expectedData, $events);
    }

    /**
     * @test
     */
    public function it_can_get_related_place_of_an_event(): void
    {
        $eventId = 'e201cea1-4a79-4834-9501-b28a92900fa1';
        $organizerId = '3a4abf90-1859-49de-a667-b713c81aad28';
        $placeId = 'e64362f5-43e1-468b-97d6-8981fb0fe426';

        $existingData = [
            (object)[
                'event' => $eventId,
                'organizer' => $organizerId,
                'place' => $placeId,
            ],
        ];
        $this->insertTableData($this->tableName, $existingData);

        $relatedPlaceId = $this->repository->getPlaceOfEvent($eventId);

        $this->assertEquals($placeId, $relatedPlaceId);
    }

    /**
     * @test
     */
    public function it_returns_null_when_event_has_no_related_place(): void
    {
        $eventId = 'e201cea1-4a79-4834-9501-b28a92900fa1';
        $organizerId = '3a4abf90-1859-49de-a667-b713c81aad28';
        $placeId = null;

        $existingData = [
            (object)[
                'event' => $eventId,
                'organizer' => $organizerId,
                'place' => $placeId,
            ],
        ];
        $this->insertTableData($this->tableName, $existingData);

        $relatedPlaceId = $this->repository->getPlaceOfEvent($eventId);

        $this->assertNull($relatedPlaceId);
    }

    /**
     * @test
     */
    public function it_can_get_related_organizer_of_an_event(): void
    {
        $eventId = 'e201cea1-4a79-4834-9501-b28a92900fa1';
        $organizerId = '3a4abf90-1859-49de-a667-b713c81aad28';
        $placeId = 'e64362f5-43e1-468b-97d6-8981fb0fe426';

        $existingData = [
            (object)[
                'event' => $eventId,
                'organizer' => $organizerId,
                'place' => $placeId,
            ],
        ];
        $this->insertTableData($this->tableName, $existingData);

        $relatedOrganizerId = $this->repository->getOrganizerOfEvent($eventId);

        $this->assertEquals($organizerId, $relatedOrganizerId);
    }

    /**
     * @test
     */
    public function it_returns_null_when_event_has_no_related_organizer(): void
    {
        $eventId = 'e201cea1-4a79-4834-9501-b28a92900fa1';
        $organizerId = null;
        $placeId = 'e64362f5-43e1-468b-97d6-8981fb0fe426';

        $existingData = [
            (object)[
                'event' => $eventId,
                'organizer' => $organizerId,
                'place' => $placeId,
            ],
        ];
        $this->insertTableData($this->tableName, $existingData);

        $relatedOrganizerId = $this->repository->getOrganizerOfEvent($eventId);

        $this->assertNull($relatedOrganizerId);
    }
}
