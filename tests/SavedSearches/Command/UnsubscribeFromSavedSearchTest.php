<?php

namespace CultuurNet\UDB3\SavedSearches\Command;

use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;

class UnsubscribeFromSavedSearchTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_the_stored_data()
    {
        $userId = new StringLiteral('some-user-id');
        $searchId = new StringLiteral('some-search-id');

        $command = new UnsubscribeFromSavedSearch($userId, $searchId);

        $this->assertEquals($userId, $command->getUserId());
        $this->assertEquals($searchId, $command->getSearchId());
    }
}
