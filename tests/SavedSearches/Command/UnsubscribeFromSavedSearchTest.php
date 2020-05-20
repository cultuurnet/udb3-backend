<?php

namespace CultuurNet\UDB3\SavedSearches\Command;

use CultuurNet\UDB3\ValueObject\SapiVersion;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;

class UnsubscribeFromSavedSearchTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_the_stored_data()
    {
        $sapiVersion = SapiVersion::V2();
        $userId = new StringLiteral('some-user-id');
        $searchId = new StringLiteral('some-search-id');

        $command = new UnsubscribeFromSavedSearch($sapiVersion, $userId, $searchId);

        $this->assertEquals($sapiVersion, $command->getSapiVersion());
        $this->assertEquals($userId, $command->getUserId());
        $this->assertEquals($searchId, $command->getSearchId());
    }
}
