<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Popularity;

use CultuurNet\UDB3\DBALTestConnectionTrait;
use DateTime;
use PHPUnit\Framework\TestCase;

class DBALPopularityRepositoryTest extends TestCase
{
    use DBALTestConnectionTrait;

    /**
     * @var DBALPopularityRepository
     */
    private $dbalPopularityRepository;

    protected function setUp(): void
    {
        $this->setUpDatabase();

        $this->getConnection()->insert(
            'offer_popularity',
            [
                'offer_id' => '388ffe26-84c3-40ec-8ab3-79466a2ab691',
                'offer_type' => 'event',
                'popularity' => 85762498,
                'creation_date' => (new DateTime())->format(\DATE_ATOM),
            ]
        );

        $this->dbalPopularityRepository = new DBALPopularityRepository($this->getConnection());
    }

    /**
     * @test
     */
    public function it_can_get_a_popularity_by_offer_id(): void
    {
        $actualPopularity = $this->dbalPopularityRepository->get('388ffe26-84c3-40ec-8ab3-79466a2ab691');

        $this->assertEquals(new Popularity(85762498), $actualPopularity);
    }

    /**
     * @test
     */
    public function it_returns_a_zero_popularity_for_a_missing_offer(): void
    {
        $actualPopularity = $this->dbalPopularityRepository->get('dbeff38d-9edd-47bf-87e2-4d2622c7a0d8');

        $this->assertEquals(new Popularity(0), $actualPopularity);
    }
}
