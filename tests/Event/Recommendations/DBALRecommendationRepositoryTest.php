<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Recommendations;

use CultuurNet\UDB3\DBALTestConnectionTrait;
use PHPUnit\Framework\TestCase;

class DBALRecommendationRepositoryTest extends TestCase
{
    use DBALTestConnectionTrait;

    private DBALRecommendationsRepository $dbalRecommendationsRepository;

    protected function setUp(): void
    {
        $this->setUpDatabase();

        $this->getConnection()->insert(
            'event_recommendations',
            [
                'event_id' => '342493ef-f0eb-412b-88e4-6693d9b12870',
                'recommended_event_id' => '1e205978-15c4-4352-b4bd-81735cf9ff49',
                'score' => 0.2,
            ]
        );
        $this->getConnection()->insert(
            'event_recommendations',
            [
                'event_id' => '342493ef-f0eb-412b-88e4-6693d9b12870',
                'recommended_event_id' => 'ab56d8f9-e92a-4eac-a430-c57a78d2988e',
                'score' => 0.13,
            ]
        );
        $this->getConnection()->insert(
            'event_recommendations',
            [
                'event_id' => 'abf44212-be46-49f6-a12d-68b7231321da',
                'recommended_event_id' => 'ab56d8f9-e92a-4eac-a430-c57a78d2988e',
                'score' => 0.33,
            ]
        );
        $this->getConnection()->insert(
            'event_recommendations',
            [
                'event_id' => '342493ef-f0eb-412b-88e4-6693d9b12870',
                'recommended_event_id' => '390b8e0a-87be-49c9-bb01-a9b97aa2c13e',
                'score' => 0.68,
            ]
        );

        $this->dbalRecommendationsRepository = new DBALRecommendationsRepository($this->getConnection());
    }

    /**
     * @test
     */
    public function it_can_get_recommendations_by_event_id(): void
    {
        $this->assertEquals(
            new Recommendations(
                new Recommendation('1e205978-15c4-4352-b4bd-81735cf9ff49', 0.2),
                new Recommendation('ab56d8f9-e92a-4eac-a430-c57a78d2988e', 0.13),
                new Recommendation('390b8e0a-87be-49c9-bb01-a9b97aa2c13e', 0.68)
            ),
            $this->dbalRecommendationsRepository->getByEvent('342493ef-f0eb-412b-88e4-6693d9b12870')
        );
    }

    /**
     * @test
     */
    public function it_handles_empty_recommendations_by_event_id(): void
    {
        $this->assertEquals(
            new Recommendations(),
            $this->dbalRecommendationsRepository->getByEvent('8701291e-8c0a-4aa1-badb-4e06c4f06ec7')
        );
    }

    /**
     * @test
     */
    public function it_can_get_recommendations_by_recommended_event_id(): void
    {
        $this->assertEquals(
            new Recommendations(
                new Recommendation('342493ef-f0eb-412b-88e4-6693d9b12870', 0.13),
                new Recommendation('abf44212-be46-49f6-a12d-68b7231321da', 0.33)
            ),
            $this->dbalRecommendationsRepository->getByRecommendedEvent('ab56d8f9-e92a-4eac-a430-c57a78d2988e')
        );
    }

    /**
     * @test
     */
    public function it_handles_empty_recommendations_by_recommended_event_id(): void
    {
        $this->assertEquals(
            new Recommendations(),
            $this->dbalRecommendationsRepository->getByRecommendedEvent('0ffe651c-9d84-4ffd-aa95-8b8158647f9f')
        );
    }
}
