<?php

namespace CultuurNet\UDB3\SavedSearches;

use CultuurNet\UDB3\SavedSearches\Properties\CreatorQueryString;
use CultuurNet\UDB3\SavedSearches\ReadModel\SavedSearch;
use CultuurNet\UDB3\SavedSearches\ValueObject\CreatedByQueryMode;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;

class Sapi3FixedSavedSearchRepositoryTest extends TestCase
{
    /**
     * @var \CultureFeed_User
     */
    protected $user;

    /**
     * @var FixedSavedSearchRepository
     */
    protected $repository;

    public function setUp()
    {
        $this->user = new \CultureFeed_User();
        $this->user->id = 'cef70b98-2d4d-40a9-95f0-762aae66ef3f';
        $this->user->mbox = 'foo@bar.com';

        $this->repository = new Sapi3FixedSavedSearchRepository(
            $this->user,
            CreatedByQueryMode::UUID()
        );
    }

    /**
     * @test
     */
    public function it_contains_a_search_of_all_events_created_by_current_user()
    {
        $name = new StringLiteral('Door mij ingevoerd');

        $query = new CreatorQueryString($this->user->id);

        $savedSearch = new SavedSearch($name, $query);

        $this->assertRepositoryContains($savedSearch);
    }

    /**
     * The assertContains method is too strict when comparing objects in
     * arrays, so we use in_array() instead.
     *
     * @param SavedSearch $savedSearch
     */
    private function assertRepositoryContains(SavedSearch $savedSearch)
    {
        $savedSearches = $this->repository->ownedByCurrentUser();
        $this->assertTrue(in_array($savedSearch, $savedSearches));
    }
}
