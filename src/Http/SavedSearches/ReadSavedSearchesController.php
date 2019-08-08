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

    /**
     * @param SavedSearchReadRepositoryCollection $savedSearchReadRepositoryCollection
     */
    public function __construct(
        SavedSearchReadRepositoryCollection $savedSearchReadRepositoryCollection
    ) {
        $this->savedSearchReadRepositoryCollection = $savedSearchReadRepositoryCollection;
    }

    /**
     * @param string $sapiVersion
     * @return JsonResponse
     */
    public function ownedByCurrentUser(string $sapiVersion)
    {
        $savedSearchRepository = $this->savedSearchReadRepositoryCollection->getRepository(
            SapiVersion::fromNative($sapiVersion)
        );

        return JsonResponse::create(
            $savedSearchRepository->ownedByCurrentUser()
        );
    }
}
