<?php

namespace CultuurNet\UDB3\SavedSearches\Command;

use CultuurNet\UDB3\SavedSearches\Properties\QueryString;
use CultuurNet\UDB3\ValueObject\SapiVersion;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;

class SubscribeToSavedSearchTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_the_stored_data()
    {
        $sapiVersion = SapiVersion::V2();
        $userId = new StringLiteral('some-user-id');
        $name = new StringLiteral('My very first saved search.');
        $query = new QueryString('city:"Leuven"');

        $command = new SubscribeToSavedSearch($sapiVersion, $userId, $name, $query);

        $this->assertEquals($sapiVersion, $command->getSapiVersion());
        $this->assertEquals($userId, $command->getUserId());
        $this->assertEquals($name, $command->getName());
        $this->assertEquals($query, $command->getQuery());
    }
}
