<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Kinepolis;

use CultuurNet\UDB3\DBALTestConnectionTrait;
use CultuurNet\UDB3\Kinepolis\Mapping\MovieMappingRepository;
use PHPUnit\Framework\TestCase;

final class MovieMappingRepositoryTest extends TestCase
{
    use DBALTestConnectionTrait;

    public const TABLE_NAME = 'kinepolis_movie_mapping';

    private string $eventId;

    private string $movieId;

    private MovieMappingRepository $movieMappingRepository;

    public function setUp(): void
    {
        $this->setUpDatabase();
        $this->eventId = '31c43ce5-4879-49a7-8627-dd1b60c5a9ed';

        $this->movieId = 'Kinepolis:tDECAm47127';

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
