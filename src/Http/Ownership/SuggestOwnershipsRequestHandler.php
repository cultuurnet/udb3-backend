<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Ownership;

use CultuurNet\UDB3\Http\Ownership\Search\SearchParameter;
use CultuurNet\UDB3\Http\Ownership\Search\SearchQuery;
use CultuurNet\UDB3\Http\Ownership\Suggestions\SuggestOwnershipsSapiQuery;
use CultuurNet\UDB3\Http\Request\QueryParameters;
use CultuurNet\UDB3\Http\Response\JsonLdResponse;
use CultuurNet\UDB3\Model\Organizer\OrganizerIDParser;
use CultuurNet\UDB3\Model\ValueObject\Identity\ItemIdentifier;
use CultuurNet\UDB3\Model\ValueObject\Identity\ItemType;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\OfferJsonDocumentReadRepository;
use CultuurNet\UDB3\Ownership\OwnershipState;
use CultuurNet\UDB3\Ownership\Repositories\OwnershipItem;
use CultuurNet\UDB3\Ownership\Repositories\Search\OwnershipSearchRepository;
use CultuurNet\UDB3\Search\ResultsGenerator;
use CultuurNet\UDB3\Search\ResultsGeneratorInterface;
use CultuurNet\UDB3\Search\SearchServiceInterface;
use CultuurNet\UDB3\Search\Sorting;
use CultuurNet\UDB3\User\CurrentUser;
use CultuurNet\UDB3\User\UserIdentityResolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class SuggestOwnershipsRequestHandler implements RequestHandlerInterface
{
    private ResultsGeneratorInterface $resultsGenerator;
    private OfferJsonDocumentReadRepository $offerRepository;
    private CurrentUser $currentUser;
    private UserIdentityResolver $userIdentityResolver;
    private OwnershipSearchRepository $ownershipSearchRepository;
    private OrganizerIDParser $organizerIDParser;

    public function __construct(
        SearchServiceInterface $searchService,
        OfferJsonDocumentReadRepository $offerRepository,
        CurrentUser $currentUser,
        UserIdentityResolver $userIdentityResolver,
        OwnershipSearchRepository $ownershipSearchRepository,
        OrganizerIDParser $organizerIDParser
    ) {
        $this->resultsGenerator = new ResultsGenerator(
            $searchService,
            new Sorting('modified', 'desc')
        );
        $this->offerRepository = $offerRepository;
        $this->currentUser = $currentUser;
        $this->userIdentityResolver = $userIdentityResolver;
        $this->ownershipSearchRepository = $ownershipSearchRepository;
        $this->organizerIDParser = $organizerIDParser;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = new QueryParameters($request);

        $queryParams->guardRequiredEnum('itemType', [ItemType::organizer()->toString()]);

        $id = $this->currentUser->getId();
        $user = $this->userIdentityResolver->getUserById($id);

        $query = (new SuggestOwnershipsSapiQuery($user))->toString();

        /**
         * A map to deduplicate returned organizers
         * @var array<string, array> $idToOrganizerMap
         */
        $idToOrganizerMap = [];

        /**
         * @var ItemIdentifier $result
         */
        foreach ($this->resultsGenerator->search($query) as $result) {
            $offerType = OfferType::fromCaseInsensitiveValue($result->getItemType()->toString());
            $id = $result->getId();

            $offer = $this->offerRepository->fetch($offerType, $id)->getAssocBody();

            $organizerId = $this->organizerIDParser->fromUrl(new Url($offer['organizer']['@id']))->toString();

            $idToOrganizerMap[$organizerId] = [
                '@id' => $offer['organizer']['@id'],
                '@type' => 'Organizer',
            ];
        }

        $activeOwnerships = $this->ownershipSearchRepository
            ->search(new SearchQuery([
                new SearchParameter('ownerId', $user->getUserId()),
                new SearchParameter('state', OwnershipState::requested()->toString()),
                new SearchParameter('state', OwnershipState::approved()->toString()),
            ]));

        /**
         * @var OwnershipItem $activeOwnership
         */
        foreach ($activeOwnerships as $activeOwnership) {
            $itemId = $activeOwnership->getItemId();

            if (isset($idToOrganizerMap[$itemId])) {
                unset($idToOrganizerMap[$itemId]);
            }
        }

        return new JsonLdResponse([
            'member' => array_values($idToOrganizerMap),
        ]);
    }
}
