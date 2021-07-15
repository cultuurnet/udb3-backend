<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SavedSearches;

use CultuurNet\UDB3\Jwt\Symfony\Authentication\Token\Token;
use CultuurNet\UDB3\SavedSearches\Properties\CreatorQueryString;
use CultuurNet\UDB3\SavedSearches\ReadModel\SavedSearch;
use CultuurNet\UDB3\SavedSearches\ReadModel\SavedSearchRepositoryInterface;
use CultuurNet\UDB3\SavedSearches\ValueObject\CreatedByQueryMode;
use ValueObjects\StringLiteral\StringLiteral;

class Sapi3FixedSavedSearchRepository implements SavedSearchRepositoryInterface
{
    /**
     * @var Token
     */
    private $token;

    /**
     * @var CreatedByQueryMode
     */
    protected $createdByQueryMode;

    public function __construct(
        Token $token,
        CreatedByQueryMode $createdByQueryMode
    ) {
        $this->token = $token;
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
        if ($this->createdByQueryMode->toNative() === CreatedByQueryMode::UUID) {
            return new CreatorQueryString($this->token->getUserId());
        }

        // If the user is not found on Auth0, just return a query that filters the creator on user id since we don't
        // have an email to filter on anyway.
        $user = $this->token->getUserIdentityDetails();
        if (!$user) {
            return new CreatorQueryString($this->token->getUserId());
        }

        // If the user is found and the mode is set to mixed, return a query that filters the creator on either email
        // or user id.
        if ($this->createdByQueryMode->toNative() === CreatedByQueryMode::MIXED) {
            return new CreatorQueryString(
                $user->getEmailAddress()->toNative(),
                $this->token->getUserId()
            );
        }

        // Otherwise return a query that filters the creator on email (original/historical behaviour).
        return new CreatorQueryString(
            $user->getEmailAddress()->toNative()
        );
    }
}
