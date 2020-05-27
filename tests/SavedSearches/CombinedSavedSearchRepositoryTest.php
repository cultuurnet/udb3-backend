<?php

namespace CultuurNet\UDB3\SavedSearches;

use CultuurNet\UDB3\SavedSearches\Properties\CreatedByQueryString;
use CultuurNet\UDB3\SavedSearches\Properties\QueryString;
use CultuurNet\UDB3\SavedSearches\ReadModel\SavedSearch;
use CultuurNet\UDB3\SavedSearches\ReadModel\SavedSearchRepositoryInterface;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;

class CombinedSavedSearchRepositoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_combine_the_results_of_multiple_repositories()
    {
        $savedSearches = [
            new SavedSearch(
                new StringLiteral('Saved search 0'),
                new QueryString('city:leuven')
            ),
            new SavedSearch(
                new StringLiteral('Saved search 1'),
                new QueryString('city:herent')
            ),
            new SavedSearch(
                new StringLiteral('Saved search 2'),
                new CreatedByQueryString('cef70b98-2d4d-40a9-95f0-762aae66ef3f')
            ),
            new SavedSearch(
                new StringLiteral('Saved search 3'),
                new QueryString('keyword:paspartoe')
            ),
        ];

        $firstRepository = $this->createMock(SavedSearchRepositoryInterface::class);
        $firstRepository->expects($this->once())
            ->method('ownedByCurrentUser')
            ->willReturn([
                $savedSearches[0],
            ]);

        $secondRepository = $this->createMock(SavedSearchRepositoryInterface::class);
        $secondRepository->expects($this->once())
            ->method('ownedByCurrentUser')
            ->willReturn([
                $savedSearches[1],
                $savedSearches[2],
            ]);

        $thirdRepository = $this->createMock(SavedSearchRepositoryInterface::class);
        $thirdRepository->expects($this->once())
            ->method('ownedByCurrentUser')
            ->willReturn([
                $savedSearches[3],
            ]);

        $combinedRepository = new CombinedSavedSearchRepository(
            $firstRepository,
            $secondRepository,
            $thirdRepository
        );

        $this->assertEquals($savedSearches, $combinedRepository->ownedByCurrentUser());
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_a_provided_argument_is_not_a_repository()
    {
        $invalidRepository = new \stdClass();
        $this->expectException(\InvalidArgumentException::class);
        new CombinedSavedSearchRepository($invalidRepository);
    }
}
