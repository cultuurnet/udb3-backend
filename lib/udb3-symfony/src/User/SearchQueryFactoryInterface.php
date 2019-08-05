<?php

namespace CultuurNet\UDB3\Symfony\User;

use Symfony\Component\HttpFoundation\Request;

interface SearchQueryFactoryInterface
{
    /**
     * @param Request $request
     * @return \CultureFeed_SearchUsersQuery
     */
    public function createSearchQueryfromRequest(Request $request);

    /**
     * @param Request $request
     * @return int
     */
    public function createPageNumberFromRequest(Request $request);

    /**
     * @param Request $request
     * @return int
     */
    public function getStartFromRequest(Request $request);

    /**
     * @param Request $request
     * @return int
     */
    public function getLimitFromRequest(Request $request);
}
