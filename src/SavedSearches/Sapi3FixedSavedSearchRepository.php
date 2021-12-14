<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SavedSearches;

use CultuurNet\UDB3\Jwt\Symfony\Authentication\JsonWebToken;
use CultuurNet\UDB3\SavedSearches\Properties\CreatorQueryString;
use CultuurNet\UDB3\SavedSearches\ReadModel\SavedSearch;
use CultuurNet\UDB3\SavedSearches\ReadModel\SavedSearchRepositoryInterface;
use CultuurNet\UDB3\SavedSearches\ValueObject\CreatedByQueryMode;
use CultuurNet\UDB3\User\UserIdentityResolver;
use ValueObjects\StringLiteral\StringLiteral;

class Sapi3FixedSavedSearchRepository implements SavedSearchRepositoryInterface
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
        $name = new StringLiteral('Door mij ingevoerd');
        $creatorQueryString = $this->getCreatorQueryString();
        return [
            new SavedSearch($name, $creatorQueryString),
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
