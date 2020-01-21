<?php

namespace CultuurNet\UDB3\User;

interface CultureFeedUserIdentityDetailsFactoryInterface
{
    /**
     * @param \CultureFeed_User $cfUser
     * @return UserIdentityDetails
     */
    public function fromCultureFeedUser(\CultureFeed_User $cfUser);

    /**
     * @param \CultureFeed_SearchUser $cfSearchUser
     * @return UserIdentityDetails
     */
    public function fromCultureFeedUserSearchResult(\CultureFeed_SearchUser $cfSearchUser);
}
