<?php

namespace CultuurNet\UDB3\Http\SavedSearches;

use CultuurNet\UDB3\SavedSearches\SavedSearchReadRepositoryCollection;
use CultuurNet\UDB3\ValueObject\SapiVersion;
use Symfony\Component\HttpFoundation\JsonResponse;

class ReadSavedSearchesController
{
    /**
     * @var SavedSearchReadRepositoryCollection
     */
    private $savedSearchReadRepositoryCollection;

    public function __construct(
        SavedSearchReadRepositoryCollection $savedSearchReadRepositoryCollection
    ) {
        $this->savedSearchReadRepositoryCollection = $savedSearchReadRepositoryCollection;
    }

    public function ownedByCurrentUser(): JsonResponse
    {
        $savedSearchRepository = $this->savedSearchReadRepositoryCollection->getRepository(
            SapiVersion::V3()
        );

        return JsonResponse::create(
            $savedSearchRepository->ownedByCurrentUser()
        );
    }
}
