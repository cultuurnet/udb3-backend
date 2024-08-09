<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SavedSearches;

use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\SavedSearches\Command\SubscribeToSavedSearch;
use CultuurNet\UDB3\SavedSearches\Command\UnsubscribeFromSavedSearch;
use CultuurNet\UDB3\SavedSearches\Command\UpdateSavedSearch;
use CultuurNet\UDB3\SavedSearches\Properties\QueryString;
use CultuurNet\UDB3\SavedSearches\WriteModel\SavedSearchRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UDB3SavedSearchesCommandHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    /**
     * @var SavedSearchRepositoryInterface&MockObject
     */
    private $savedSearchesRepository;

    private UDB3SavedSearchesCommandHandler $udb3SavedSearchesCommandHandler;

    protected function setUp(): void
    {
        $this->savedSearchesRepository = $this->createMock(SavedSearchRepositoryInterface::class);
        $this->udb3SavedSearchesCommandHandler = new UDB3SavedSearchesCommandHandler($this->savedSearchesRepository);
    }

    /**
     * @test
     */
    public function it_can_handle_subscribe_to_saved_search_commands(): void
    {
        $id = '3c504b25-b221-4aa5-ad75-5510379ba502';
        $userId = 'some-user-id';
        $name = 'My very first saved search!';
        $query = new QueryString('city:"Leuven"');

        $subscribeToSavedSearch = new SubscribeToSavedSearch($id, $userId, $name, $query);

        $this->savedSearchesRepository->expects($this->once())
            ->method('insert')
            ->with(
                $id,
                $userId,
                $name,
                $query
            );

        $this->udb3SavedSearchesCommandHandler->handle($subscribeToSavedSearch);
    }

    /**
     * @test
     */
    public function it_can_handle_updates_to_saved_search_commands(): void
    {
        $id = '9b68b83c-366e-4d64-9d5d-fba58ef8b94f';
        $userId = '2bf5b8bf-7dbf-4a21-ab31-6da376cb315b';
        $name = 'My very first saved search!';
        $query = new QueryString('city:"Leuven"');

        $subscribeToSavedSearch = new UpdateSavedSearch($id, $userId, $name, $query);

        $this->savedSearchesRepository->expects($this->once())
            ->method('update')
            ->with(
                $id,
                $userId,
                $name,
                $query
            );

        $this->udb3SavedSearchesCommandHandler->handle($subscribeToSavedSearch);
    }

    /**
     * @test
     */
    public function it_can_handle_unsubscribe_from_saved_search_commands(): void
    {
        $userId = 'some-user-id';
        $searchId = 'some-search-id';

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
