<?php

namespace CultuurNet\UDB3\Http\MyOrganizers;

use CultuurNet\Hydra\PagedCollection;
use CultuurNet\Hydra\Symfony\PageUrlGenerator;
use CultuurNet\UDB3\MyOrganizers\MyOrganizersLookupServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGenerator;
use ValueObjects\Number\Natural;

class MyOrganizersController
{
    private const PAGE_ITEM_LIMIT = 50;
    private const PAGE_PARAMETER = 'page';

    /**
     * @var \CultureFeed_User
     */
    private $currentUser;

    /**
     * @var UrlGenerator
     */
    private $urlGenerator;

    /**
     * @var MyOrganizersLookupServiceInterface
     */
    private $lookup;

    /**
     * DashboardRestController constructor.
     * @param string $routeName
     * @param \CultureFeed_User $currentUser
     * @param UrlGenerator $urlGenerator
     * @param MyOrganizersLookupServiceInterface $lookup
     */
    public function __construct(
        string $routeName,
        \CultureFeed_User $currentUser,
        UrlGenerator $urlGenerator,
        MyOrganizersLookupServiceInterface $lookup
    ) {
        $this->routeName = $routeName;
        $this->lookup = $lookup;
        $this->currentUser = $currentUser;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function itemsOwnedByCurrentUser(Request $request)
    {
        $pageNumber = intval($request->query->get(self::PAGE_PARAMETER, 1));

        $limit = self::PAGE_ITEM_LIMIT;

        $partOfCollection = $this->lookup->itemsOwnedByUser(
            $this->currentUser->id,
            Natural::fromNative($limit),
            Natural::fromNative(--$pageNumber * $limit)
        );

        $pageUrlFactory = new PageUrlGenerator(
            $request->query,
            $this->urlGenerator,
            $this->routeName,
            self::PAGE_PARAMETER
        );

        return JsonResponse::create(
            new PagedCollection(
                $pageNumber,
                $limit,
                $partOfCollection->getItems(),
                $partOfCollection->getTotal()->toNative(),
                $pageUrlFactory
            )
        );
    }
}
