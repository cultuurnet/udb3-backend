<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SavedSearches;

use CultuurNet\UDB3\Http\Auth\Jwt\JsonWebToken;
use CultuurNet\UDB3\Http\Ownership\Search\SearchParameter;
use CultuurNet\UDB3\Http\Ownership\Search\SearchQuery;
use CultuurNet\UDB3\Ownership\OwnershipState;
use CultuurNet\UDB3\Ownership\Repositories\Search\OwnershipSearchRepository;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\SavedSearches\Properties\QueryString;
use CultuurNet\UDB3\SavedSearches\ReadModel\SavedSearch;
use CultuurNet\UDB3\SavedSearches\ReadModel\SavedSearchesOwnedByCurrentUser;

class OwnershipSavedSearchRepository implements SavedSearchesOwnedByCurrentUser
{
    private JsonWebToken $token;

    private DocumentRepository $organizerDocumentRepository;

    private OwnershipSearchRepository $ownershipSearchRepository;

    public function __construct(
        JsonWebToken $token,
        DocumentRepository $organizerDocumentRepository,
        OwnershipSearchRepository $ownershipSearchRepository
    ) {
        $this->token = $token;
        $this->organizerDocumentRepository = $organizerDocumentRepository;
        $this->ownershipSearchRepository = $ownershipSearchRepository;
    }

    /**
     * @return SavedSearch[]
     */
    public function ownedByCurrentUser(): array
    {
        $ownershipQueryStrings = $this->getOwnershipQueryStrings();

        $savedSearches = [];
        foreach ($ownershipQueryStrings as $organizerName => $ownershipQueryString) {
            $savedSearches[] = new SavedSearch('Aanbod ' . $organizerName, $ownershipQueryString);
        }
        return $savedSearches;
    }

    /**
     * @return QueryString[]
     */
    private function getOwnershipQueryStrings(): array
    {
        $userId = $this->token->getUserId();

        $ownershipItemCollection = $this->ownershipSearchRepository->search(
            new SearchQuery([
                new SearchParameter('state', OwnershipState::approved()->toString()),
                new SearchParameter('itemType', 'organizer'),
                new SearchParameter('ownerId', $userId),
            ])
        );

        $ownershipQueries = [];

        foreach ($ownershipItemCollection as $ownershipItem) {
            $organizerId = $ownershipItem->getItemId();
            $organizerAsJson = $this->organizerDocumentRepository->fetch($organizerId);
            $body = $organizerAsJson->getAssocBody();
            $organizerName = $this->getNameInMainLanguage($body);
            $ownershipQueries[$organizerName] = new QueryString('organizer.id:' . $organizerId);
        }
        return $ownershipQueries;
    }

    private function getNameInMainLanguage(array $body): string
    {
        $mainLanguage = $body['mainLanguage'];
        return $body['name'][$mainLanguage];
    }
}
