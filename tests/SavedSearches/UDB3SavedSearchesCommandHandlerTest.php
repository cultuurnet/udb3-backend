<?php

namespace CultuurNet\UDB3\SavedSearches;

use CultuurNet\UDB3\SavedSearches\Command\SubscribeToSavedSearch;
use CultuurNet\UDB3\SavedSearches\Command\UnsubscribeFromSavedSearch;
use CultuurNet\UDB3\SavedSearches\Properties\QueryString;
use CultuurNet\UDB3\SavedSearches\WriteModel\SavedSearchRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;

class UDB3SavedSearchesCommandHandlerTest extends TestCase
{
    /**
     * @var SavedSearchRepositoryInterface|MockObject
     */
    private $savedSearchesRepository;

    /**
     * @var UDB3SavedSearchesCommandHandler
     */
    private $udb3SavedSearchesCommandHandler;

    protected function setUp(): void
    {
        $this->savedSearchesRepository = $this->createMock(SavedSearchRepositoryInterface::class);
        $this->udb3SavedSearchesCommandHandler = new UDB3SavedSearchesCommandHandler($this->savedSearchesRepository);
    }

    /**
     * @test
     */
    public function it_can_handle_subscribe_to_saved_search_commands()
    {
        $userId = new StringLiteral('some-user-id');
        $name = new StringLiteral('My very first saved search!');
        $query = new QueryString('city:"Leuven"');

        $subscribeToSavedSearch = new SubscribeToSavedSearch($userId, $name, $query);

        $this->savedSearchesRepository->expects($this->once())
            ->method('write')
            ->with(
                $userId,
                $name,
                $query
            );

        $this->udb3SavedSearchesCommandHandler->handle($subscribeToSavedSearch);
    }

    /**
     * @test
     */
    public function it_can_handle_unsubscribe_from_saved_search_commands()
    {
        $userId = new StringLiteral('some-user-id');
        $searchId = new StringLiteral('some-search-id');

        $unsubscribeFromSavedSearch = new UnsubscribeFromSavedSearch($userId, $searchId);

        $this->savedSearchesRepository->expects($this->once())
            ->method('delete')
            ->with(
                $userId,
                $searchId
            );

        $this->udb3SavedSearchesCommandHandler->handle($unsubscribeFromSavedSearch);
    }
}
