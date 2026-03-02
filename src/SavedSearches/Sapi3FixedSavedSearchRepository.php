<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SavedSearches;

use CultuurNet\UDB3\Http\Auth\Jwt\JsonWebToken;
use CultuurNet\UDB3\SavedSearches\Properties\CreatorQueryString;
use CultuurNet\UDB3\SavedSearches\ReadModel\SavedSearch;
use CultuurNet\UDB3\SavedSearches\ReadModel\SavedSearchesOwnedByCurrentUser;
use CultuurNet\UDB3\SavedSearches\ValueObject\CreatedByQueryMode;
use CultuurNet\UDB3\User\UserIdentityResolver;

class Sapi3FixedSavedSearchRepository implements SavedSearchesOwnedByCurrentUser
{
    private JsonWebToken $token;

    private UserIdentityResolver $userIdentityResolver;

    protected CreatedByQueryMode $createdByQueryMode;

    public function __construct(
        JsonWebToken $token,
        UserIdentityResolver $userIdentityResolver,
        CreatedByQueryMode $createdByQueryMode
    ) {
        $this->token = $token;
        $this->userIdentityResolver = $userIdentityResolver;
        $this->createdByQueryMode = $createdByQueryMode;
    }

    /**
     * @return SavedSearch[]
     */
    public function ownedByCurrentUser(): array
    {
        $creatorQueryString = $this->getCreatorQueryString();
        return [
            new SavedSearch('Door mij ingevoerd', $creatorQueryString),
        ];
    }

    private function getCreatorQueryString(): CreatorQueryString
    {
        // If the creator query mode is set to uuid only, return early to avoid fetching user info from auth0 because
        // it's not needed.
        if ($this->createdByQueryMode->sameAs(CreatedByQueryMode::uuid())) {
            return new CreatorQueryString($this->token->getUserId());
        }

        // If the user is not found on Auth0, just return a query that filters the creator on user id since we don't
        // have an email to filter on anyway.
        $user = $this->token->getUserIdentityDetails($this->userIdentityResolver);
        if (!$user) {
            return new CreatorQueryString($this->token->getUserId());
        }

        // If the user is found and the mode is set to mixed, return a query that filters the creator on either email
        // or user id.
        if ($this->createdByQueryMode->sameAs(CreatedByQueryMode::mixed())) {
            return new CreatorQueryString(
                $user->getEmailAddress(),
                $this->token->getUserId()
            );
        }

        // Otherwise return a query that filters the creator on email (original/historical behaviour).
        return new CreatorQueryString(
            $user->getEmailAddress()
        );
    }
}
