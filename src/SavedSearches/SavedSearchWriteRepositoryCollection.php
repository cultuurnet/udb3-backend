<?php

namespace CultuurNet\UDB3\SavedSearches;

use CultuurNet\UDB3\SavedSearches\WriteModel\SavedSearchRepositoryInterface;
use CultuurNet\UDB3\ValueObject\SapiVersion;

class SavedSearchWriteRepositoryCollection
{
    /**
     * @var SavedSearchRepositoryInterface
     */
    private $savedSearchRepositories;

    /**
     * @param SapiVersion $sapiVersion
     * @param SavedSearchRepositoryInterface $savedSearchRepository
     * @return SavedSearchWriteRepositoryCollection
     */
    public function withRepository(
        SapiVersion $sapiVersion,
        SavedSearchRepositoryInterface $savedSearchRepository
    ): SavedSearchWriteRepositoryCollection {
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
