<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SavedSearches;

use CultuurNet\UDB3\SavedSearches\Properties\CreatedByQueryString;
use CultuurNet\UDB3\SavedSearches\Properties\QueryString;
use CultuurNet\UDB3\SavedSearches\ReadModel\SavedSearch;
use CultuurNet\UDB3\SavedSearches\ReadModel\SavedSearchesOwnedByCurrentUser;
use PHPUnit\Framework\TestCase;

class CombinedSavedSearchRepositoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_combine_the_results_of_multiple_repositories(): void
    {
        $savedSearches = [
            new SavedSearch(
                'Saved search 0',
                new QueryString('city:leuven')
            ),
            new SavedSearch(
                'Saved search 1',
                new QueryString('city:herent')
            ),
            new SavedSearch(
                'Saved search 2',
                new CreatedByQueryString('cef70b98-2d4d-40a9-95f0-762aae66ef3f')
            ),
            new SavedSearch(
                'Saved search 3',
                new QueryString('keyword:paspartoe')
            ),
        ];

        $firstRepository = $this->createMock(SavedSearchesOwnedByCurrentUser::class);
        $firstRepository->expects($this->once())
            ->method('ownedByCurrentUser')
            ->willReturn([
                $savedSearches[0],
            ]);

        $secondRepository = $this->createMock(SavedSearchesOwnedByCurrentUser::class);
        $secondRepository->expects($this->once())
            ->method('ownedByCurrentUser')
            ->willReturn([
                $savedSearches[1],
                $savedSearches[2],
            ]);

        $thirdRepository = $this->createMock(SavedSearchesOwnedByCurrentUser::class);
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
}
