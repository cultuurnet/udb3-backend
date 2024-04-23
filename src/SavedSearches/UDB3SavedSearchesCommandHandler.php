<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SavedSearches;

use Broadway\CommandHandling\SimpleCommandHandler;
use CultuurNet\UDB3\SavedSearches\Command\SubscribeToSavedSearch;
use CultuurNet\UDB3\SavedSearches\Command\UnsubscribeFromSavedSearch;
use CultuurNet\UDB3\SavedSearches\Command\UpdateSavedSearch;
use CultuurNet\UDB3\SavedSearches\WriteModel\SavedSearchRepositoryInterface;

class UDB3SavedSearchesCommandHandler extends SimpleCommandHandler
{
    private SavedSearchRepositoryInterface $savedSearchRepository;

    public function __construct(SavedSearchRepositoryInterface $savedSearchRepository)
    {
        $this->savedSearchRepository = $savedSearchRepository;
    }

    public function handleSubscribeToSavedSearch(SubscribeToSavedSearch $subscribeToSavedSearch): void
    {
        $id = $subscribeToSavedSearch->getId();
        $userId = $subscribeToSavedSearch->getUserId();
        $name = $subscribeToSavedSearch->getName();
        $query = $subscribeToSavedSearch->getQuery();

        $this->savedSearchRepository->insert($id, $userId, $name, $query);
    }

    public function handleUpdateSavedSearch(UpdateSavedSearch $subscribeToSavedSearch): void
    {
        $userId = $subscribeToSavedSearch->getUserId();
        $name = $subscribeToSavedSearch->getName();
        $query = $subscribeToSavedSearch->getQuery();
        $id = $subscribeToSavedSearch->getId();

        $this->savedSearchRepository->update($id, $userId, $name, $query);
    }

    public function handleUnsubscribeFromSavedSearch(UnsubscribeFromSavedSearch $unsubscribeFromSavedSearch): void
    {
        $userId = $unsubscribeFromSavedSearch->getUserId();
        $searchId = $unsubscribeFromSavedSearch->getSearchId();

        $this->savedSearchRepository->delete($userId, $searchId);
    }
}
