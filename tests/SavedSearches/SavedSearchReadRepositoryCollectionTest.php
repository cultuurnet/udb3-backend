<?php

namespace CultuurNet\UDB3\SavedSearches;

use CultuurNet\UDB3\SavedSearches\ReadModel\SavedSearchRepositoryInterface;
use CultuurNet\UDB3\ValueObject\SapiVersion;
use PHPUnit\Framework\TestCase;

class SavedSearchReadRepositoryCollectionTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_store_saved_search_repositories(): void
    {
        $savedSearchRepositoryCollection = new SavedSearchReadRepositoryCollection();

        $sapi2SavedSearchRepository = $this->createMock(SavedSearchRepositoryInterface::class);
        $sapi3SavedSearchRepository = $this->createMock(SavedSearchRepositoryInterface::class);

        $savedSearchRepositoryCollection = $savedSearchRepositoryCollection->withRepository(
            SapiVersion::V2(),
            $sapi2SavedSearchRepository
        );

        $savedSearchRepositoryCollection = $savedSearchRepositoryCollection->withRepository(
            SapiVersion::V3(),
            $sapi3SavedSearchRepository
        );

        $this->assertEquals(
            $sapi2SavedSearchRepository,
            $savedSearchRepositoryCollection->getRepository(
                SapiVersion::V2()
            )
        );

        $this->assertEquals(
            $sapi3SavedSearchRepository,
            $savedSearchRepositoryCollection->getRepository(
                SapiVersion::V3()
            )
        );
    }

    /**
     * @test
     */
    public function it_return_null_when_repo_not_found_for_given_sapi_version(): void
    {
        $savedSearchRepositoryCollection = new SavedSearchReadRepositoryCollection();

        $sapi2SavedSearchRepository = $this->createMock(SavedSearchRepositoryInterface::class);

        $savedSearchRepositoryCollection->withRepository(
            SapiVersion::V2(),
            $sapi2SavedSearchRepository
        );

        $this->assertNull(
            $savedSearchRepositoryCollection->getRepository(SapiVersion::V3())
        );
    }
}
