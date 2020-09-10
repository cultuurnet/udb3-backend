<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Productions;

use CultuurNet\UDB3\DBALTestConnectionTrait;
use CultuurNet\UDB3\Event\Productions\Doctrine\SkippedSimilarEventsSchemaConfigurator;
use PDO;
use PHPUnit\Framework\TestCase;
use Rhumsaa\Uuid\Uuid;

class SkippedSimilarEventsRepositoryTest extends TestCase
{
    use DBALTestConnectionTrait;

    /**
     * @var SkippedSimilarEventsRepository
     */
    private $repository;

    protected function setUp(): void
    {
        $schema = $this->createSchema();
        $this->createTable(SkippedSimilarEventsSchemaConfigurator::getTableDefinition($schema));

        $this->repository = new SkippedSimilarEventsRepository($this->getConnection());
    }

    /**
     * @test
     */
    public function it_can_add_skipped_similar_event_pairs(): void
    {
        $event1 = Uuid::uuid4()->toString();
        $event2 = Uuid::uuid4()->toString();
        $eventPair = new SimilarEventPair($event1, $event2);

        $this->repository->add($eventPair);

        $this->assertSkippedSimilarEventExists($event1, $event2);
    }

    private function assertSkippedSimilarEventExists(string $event1, string $event2): void
    {
        $table = $this->repository->getTableName()->toNative();
        $sql = "SELECT * FROM $table WHERE event1 = :event1 AND event2 = :event2";
        $result = $this->getConnection()->executeQuery(
            $sql,
            ['event1' => $event1, 'event2' => $event2]
        )->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals(['event1' => $event1, 'event2' => $event2], $result);
    }
}
