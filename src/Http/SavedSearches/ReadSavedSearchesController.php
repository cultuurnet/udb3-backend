<?php

namespace CultuurNet\UDB3\Http\SavedSearches;

use CultuurNet\UDB3\SavedSearches\ReadModel\SavedSearchRepositoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class ReadSavedSearchesController
{
    /**
     * @var SavedSearchRepositoryInterface
     */
    private $savedSearchRepository;

    public function __construct(SavedSearchRepositoryInterface $savedSearchRepository)
    {
        $this->savedSearchRepository = $savedSearchRepository;
    }

    public function ownedByCurrentUser(): JsonResponse
    {
        return JsonResponse::create(
            $this->savedSearchRepository->ownedByCurrentUser()
        );
    }
}
