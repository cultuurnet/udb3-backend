<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SavedSearches\Command;

use CultuurNet\UDB3\SavedSearches\Properties\QueryString;
use PHPUnit\Framework\TestCase;

class SubscribeToSavedSearchTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_the_stored_data(): void
    {
        $userId = 'some-user-id';
        $name = 'My very first saved search.';
        $query = new QueryString('city:"Leuven"');

        $command = new SubscribeToSavedSearch($userId, $name, $query);

        $this->assertEquals($userId, $command->getUserId());
        $this->assertEquals($name, $command->getName());
        $this->assertEquals($query, $command->getQuery());
    }
}
