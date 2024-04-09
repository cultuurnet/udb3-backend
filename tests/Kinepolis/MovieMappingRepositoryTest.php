<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Kinepolis;

use CultuurNet\UDB3\DBALTestConnectionTrait;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use PHPUnit\Framework\TestCase;

final class MovieMappingRepositoryTest extends TestCase
{
    use DBALTestConnectionTrait;

    public const TABLE_NAME = 'kinepolis_movie_mapping';

    private string $eventId;

    private string $movieId;

    private MovieMappingRepository $movieMappingRepository;

    private static function getTableDefinition(Schema $schema): Table
    {
        $table = $schema->createTable(self::TABLE_NAME);

        $table->addColumn('event_id', Types::GUID)->setLength(36)->setNotnull(true);
        $table->addColumn('movie_id', Types::STRING)->setNotnull(true);

        return $table;
    }

    public function setUp(): void
    {
        $this->eventId = '31c43ce5-4879-49a7-8627-dd1b60c5a9ed';

        $this->movieId = 'Kinepolis:tDECAm47127';

        $this->createTable(
            self::getTableDefinition(
                $this->createSchema()
            )
        );

        $this->getConnection()->insert(
            self::TABLE_NAME,
            [
                'event_id' => $this->eventId,
                'movie_id' => $this->movieId,
            ]
        );

        $this->movieMappingRepository = new MovieMappingRepository(
            $this->getConnection()
        );
    }

    /**
     * @test
     */
    public function it_gets_the_event_id_for_a_given_movie(): void
    {
        $this->assertEquals(
            $this->eventId,
            $this->movieMappingRepository->getByMovieId($this->movieId)
        );
    }

    /**
     * @test
     */
    public function it_returns_null_for_a_new_movie_id(): void
    {
        $this->assertNull(
            $this->movieMappingRepository->getByMovieId('Kinepolis:tKOOSTm21298')
        );
    }

    /**
     * @test
     */
    public function it_can_save_a_new_relation(): void
    {
        $newEventId = 'a25ec271-a19b-4881-9498-44b1ad4711a3';
        $newMovieId = 'Kinepolis:tKOOSTm21298';

        $this->movieMappingRepository->create($newEventId, $newMovieId);

        $this->assertEquals(
            $newEventId,
            $this->movieMappingRepository->getByMovieId($newMovieId)
        );
    }
}
