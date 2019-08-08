<?php

namespace CultuurNet\UDB3\Http\User;

use CultuurNet\Hydra\PagedCollection;
use CultuurNet\UDB3\Http\JsonLdResponse;
use CultuurNet\UDB3\User\CultureFeedUserIdentityDetailsFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SearchUserController
{
    /**
     * @var \ICultureFeed
     */
    private $cultureFeed;

    /**
     * @var SearchQueryFactoryInterface
     */
    private $searchQueryFactory;

    /**
     * @var CultureFeedUserIdentityDetailsFactoryInterface
     */
    private $cfUserIdentityFactory;

    /**
     * @param \ICultureFeed $cultureFeed
     * @param SearchQueryFactoryInterface $searchQueryFactory
     * @param CultureFeedUserIdentityDetailsFactoryInterface $cfUserIdentityFactory
     */
    public function __construct(
        \ICultureFeed $cultureFeed,
        SearchQueryFactoryInterface $searchQueryFactory,
        CultureFeedUserIdentityDetailsFactoryInterface $cfUserIdentityFactory
    ) {
        $this->cultureFeed = $cultureFeed;
        $this->searchQueryFactory = $searchQueryFactory;
        $this->cfUserIdentityFactory = $cfUserIdentityFactory;
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function search(Request $request)
    {
        $searchQuery = $this->searchQueryFactory->createSearchQueryfromRequest($request);
        $pageNumber = $this->searchQueryFactory->createPageNumberFromRequest($request);
        $limit = $this->searchQueryFactory->getLimitFromRequest($request);

        /* @var \CultureFeed_ResultSet $results */
        $results = $this->cultureFeed->searchUsers($searchQuery);

        $users = array_map(
            function (\CultureFeed_SearchUser $cfSearchUser) {
                return $this->cfUserIdentityFactory->fromCultureFeedUserSearchResult($cfSearchUser);
            },
            $results->objects
        );

        return JsonLdResponse::create()
            ->setData(
                new PagedCollection(
                    $pageNumber,
                    $limit,
                    $users,
                    $results->total
                )
            )
            ->setPublic()
            ->setClientTtl(60 * 1)
            ->setTtl(60 * 5);
    }
}
