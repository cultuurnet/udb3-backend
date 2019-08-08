<?php

namespace CultuurNet\UDB3\Symfony\User;

use Symfony\Component\HttpFoundation\Request;
use ValueObjects\Web\EmailAddress;

class SearchQueryFactory implements SearchQueryFactoryInterface
{
    /**
     * @var string
     */
    private $startQueryParameter = 'start';

    /**
     * @var string
     */
    private $limitQueryParameter = 'limit';

    /**
     * @var string
     */
    private $emailQueryParameter = 'email';

    /**
     * @var int
     */
    private $defaultStart;

    /**
     * @var int
     */
    private $defaultLimit;

    /**
     * @param int $defaultStart
     * @param int $defaultLimit
     */
    public function __construct($defaultStart = 0, $defaultLimit = 30)
    {
        $this->defaultStart = $defaultStart;
        $this->defaultLimit = $defaultLimit;
    }

    /**
     * @param Request $request
     * @return \CultureFeed_SearchUsersQuery
     */
    public function createSearchQueryfromRequest(Request $request)
    {
        $searchQuery = new \CultureFeed_SearchUsersQuery();
        $searchQuery->start = $this->getStartFromRequest($request);
        $searchQuery->max = $this->getLimitFromRequest($request);

        if ($request->query->get($this->emailQueryParameter, false)) {
            $email = new EmailAddress($request->query->get('email'));
            $searchQuery->mbox = $email->toNative();
            $searchQuery->mboxIncludePrivate = true;
        }

        return $searchQuery;
    }

    /**
     * @param Request $request
     * @return int
     */
    public function createPageNumberFromRequest(Request $request)
    {
        return (int) (1 + floor($this->getStartFromRequest($request) / $this->getLimitFromRequest($request)));
    }

    /**
     * @param Request $request
     * @return int
     */
    public function getStartFromRequest(Request $request)
    {
        return (int) $request->query->get($this->startQueryParameter, $this->defaultStart);
    }

    /**
     * @param Request $request
     * @return int
     */
    public function getLimitFromRequest(Request $request)
    {
        return (int) $request->query->get($this->limitQueryParameter, $this->defaultLimit);
    }
}
