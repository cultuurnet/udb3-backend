<?php

namespace CultuurNet\UDB3\SavedSearches;

use CultuurNet\UDB3\SavedSearches\ReadModel\SavedSearchRepositoryInterface;
use CultuurNet\UDB3\ValueObject\SapiVersion;

class SavedSearchReadRepositoryCollection
{
    /**
     * @var SavedSearchRepositoryInterface
     */
    private $savedSearchRepositories;

    /**
     * @param SapiVersion $sapiVersion
     * @param SavedSearchRepositoryInterface $savedSearchRepository
     * @return SavedSearchReadRepositoryCollection
     */
    public function withRepository(
        SapiVersion $sapiVersion,
        SavedSearchRepositoryInterface $savedSearchRepository
    ): SavedSearchReadRepositoryCollection {
        $c = clone $this;
        $c->savedSearchRepositories[$sapiVersion->toNative()] = $savedSearchRepository;
        return $c;
    }

    /**
     * @param SapiVersion $sapiVersion
     * @return SavedSearchRepositoryInterface|null
     */
    public function getRepository(SapiVersion $sapiVersion): ?SavedSearchRepositoryInterface
    {
        if (!isset($this->savedSearchRepositories[$sapiVersion->toNative()])) {
            return null;
        }

        return $this->savedSearchRepositories[$sapiVersion->toNative()];
    }
}
