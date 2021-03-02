<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\User;

interface CultureFeedUserIdentityDetailsFactoryInterface
{
    /**
     * @return UserIdentityDetails
     */
    public function fromCultureFeedUser(\CultureFeed_User $cfUser);

    /**
     * @return UserIdentityDetails
     */
    public function fromCultureFeedUserSearchResult(\CultureFeed_SearchUser $cfSearchUser);
}
