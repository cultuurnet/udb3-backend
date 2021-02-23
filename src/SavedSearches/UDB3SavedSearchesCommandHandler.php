<?php

namespace CultuurNet\UDB3\SavedSearches;

use Broadway\CommandHandling\SimpleCommandHandler;
use CultuurNet\UDB3\SavedSearches\Command\SubscribeToSavedSearch;
use CultuurNet\UDB3\SavedSearches\Command\UnsubscribeFromSavedSearch;
use CultuurNet\UDB3\SavedSearches\WriteModel\SavedSearchRepositoryInterface;

class UDB3SavedSearchesCommandHandler extends SimpleCommandHandler
{
    /**
     * @var SavedSearchRepositoryInterface
     */
    private $savedSearchRepository;

    public function __construct(SavedSearchRepositoryInterface $savedSearchRepository)
    {
        $this->savedSearchRepository = $savedSearchRepository;
    }

    public function handleSubscribeToSavedSearch(SubscribeToSavedSearch $subscribeToSavedSearch): void
    {
        $userId = $subscribeToSavedSearch->getUserId();
        $name = $subscribeToSavedSearch->getName();
        $query = $subscribeToSavedSearch->getQuery();

        $this->savedSearchRepository->write($userId, $name, $query);
    }

    public function handleUnsubscribeFromSavedSearch(UnsubscribeFromSavedSearch $unsubscribeFromSavedSearch): void
    {
        $userId = $unsubscribeFromSavedSearch->getUserId();
        $searchId = $unsubscribeFromSavedSearch->getSearchId();

        $this->savedSearchRepository->delete($userId, $searchId);
    }
}
