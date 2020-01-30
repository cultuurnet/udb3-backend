<?php

namespace CultuurNet\UDB3\User;

use ICultureFeed;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\EmailAddress;

class CultureFeedUserIdentityResolver implements UserIdentityResolverInterface
{
    /**
     * @var ICultureFeed
     */
    private $cultureFeed;

    /**
     * @var CultureFeedUserIdentityDetailsFactoryInterface
     */
    private $userIdentityDetailsFactory;

    public function __construct(
        ICultureFeed $cultureFeed,
        CultureFeedUserIdentityDetailsFactoryInterface $userIdentityDetailsFactory
    ) {
        $this->cultureFeed = $cultureFeed;
        $this->userIdentityDetailsFactory = $userIdentityDetailsFactory;
    }

    public function getUserById(StringLiteral $userId): ?UserIdentityDetails
    {
        $query = new \CultureFeed_SearchUsersQuery();
        $query->userId = $userId->toNative();

        $user = $this->searchSingleUser($query);

        if ($user && $user->getUserId()->toNative() == $userId->toNative()) {
            return $user;
        } else {
            return null;
        }
    }

    public function getUserByEmail(EmailAddress $email): ?UserIdentityDetails
    {
        $query = new \CultureFeed_SearchUsersQuery();
        $query->mbox = $email->toNative();
        $query->mboxIncludePrivate = true;

        $user = $this->searchSingleUser($query);

        // Given e-mail address could contain a wildcard (eg. *@cultuurnet.be),
        // so we should make sure the emails are exactly the same, otherwise
        // we're just returning the first user that matches the wildcard which
        // is not intended.
        if ($user && strcasecmp($user->getEmailAddress()->toNative(), $email->toNative()) === 0) {
            return $user;
        } else {
            return null;
        }
    }

    public function getUserByNick(StringLiteral $nick): ?UserIdentityDetails
    {
        $query = new \CultureFeed_SearchUsersQuery();
        $query->nick = $nick->toNative();

        $user = $this->searchSingleUser($query);

        // Given nick could contain a wildcard (eg. *somepartofnick*), so we
        // should make sure the nicks are exactly the same, otherwise we're
        // just returning the first user that matches the wildcard which is not
        // intended.
        if ($user && strcasecmp($user->getUserName()->toNative(), $nick->toNative()) === 0) {
            return $user;
        } else {
            return null;
        }
    }

    /**
     * @param \CultureFeed_SearchUsersQuery $query
     * @return UserIdentityDetails|null
     */
    private function searchSingleUser(\CultureFeed_SearchUsersQuery $query): ?UserIdentityDetails
    {
        /** @var \CultureFeed_ResultSet $results */
        $results = $this->cultureFeed->searchUsers($query);

        /** @var \CultureFeed_SearchUser $user */
        $user = reset($results->objects);

        if ($user) {
            return $this->userIdentityDetailsFactory->fromCultureFeedUserSearchResult($user);
        } else {
            return null;
        }
    }
}
