<?php

namespace CultuurNet\UDB3\SavedSearches;

use CultuurNet\UDB3\SavedSearches\Properties\CreatorQueryString;
use CultuurNet\UDB3\SavedSearches\ReadModel\SavedSearch;
use CultuurNet\UDB3\SavedSearches\ReadModel\SavedSearchRepositoryInterface;
use CultuurNet\UDB3\SavedSearches\ValueObject\CreatedByQueryMode;
use ValueObjects\StringLiteral\StringLiteral;

/**
 * Sapi3FixedSavedSearchRepository is used for Sapi3.
 *
 * Class FixedSavedSearchRepository
 * @package CultuurNet\UDB3\SavedSearches
 */
class Sapi3FixedSavedSearchRepository implements SavedSearchRepositoryInterface
{
    /**
     * @var \CultureFeed_User
     */
    private $user;

    /**
     * @var CreatedByQueryMode
     */
    protected $createdByQueryMode;

    /**
     * @param \CultureFeed_User $user
     * @param CreatedByQueryMode $createdByQueryMode
     */
    public function __construct(
        \CultureFeed_User $user,
        CreatedByQueryMode $createdByQueryMode
    ) {
        $this->user = $user;
        $this->createdByQueryMode = $createdByQueryMode;
    }

    /**
     * @return SavedSearch[]
     */
    public function ownedByCurrentUser(): array
    {
        $name = new StringLiteral('Door mij ingevoerd');

        switch ($this->createdByQueryMode->toNative()) {
            case CreatedByQueryMode::EMAIL:
                $createdByQueryString = new CreatorQueryString(
                    $this->user->mbox
                );
                break;
            case CreatedByQueryMode::MIXED:
                $createdByQueryString = new CreatorQueryString(
                    $this->user->mbox,
                    $this->user->id
                );
                break;
            default:
                $createdByQueryString = new CreatorQueryString(
                    $this->user->id
                );
        }

        return [
            new SavedSearch($name, $createdByQueryString),
        ];
    }
}
