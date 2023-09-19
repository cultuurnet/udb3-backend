<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SavedSearches\Command;

use PHPUnit\Framework\TestCase;

class UnsubscribeFromSavedSearchTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_the_stored_data(): void
    {
        $userId = 'some-user-id';
        $searchId = 'some-search-id';

        $command = new UnsubscribeFromSavedSearch($userId, $searchId);

        $this->assertEquals($userId, $command->getUserId());
        $this->assertEquals($searchId, $command->getSearchId());
    }
}
