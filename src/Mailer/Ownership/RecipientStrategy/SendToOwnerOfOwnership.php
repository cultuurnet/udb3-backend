<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Mailer\Ownership\RecipientStrategy;

use CultuurNet\UDB3\Ownership\Repositories\OwnershipItem;
use CultuurNet\UDB3\User\Recipients;
use CultuurNet\UDB3\User\UserIdentityDetails;
use CultuurNet\UDB3\User\UserIdentityResolver;
use Psr\Log\LoggerInterface;

class SendToOwnerOfOwnership implements RecipientStrategy
{
    private UserIdentityResolver $identityResolver;

    public function __construct(UserIdentityResolver $identityResolver)
    {
        $this->identityResolver = $identityResolver;
    }

    public function getRecipients(OwnershipItem $item): Recipients
    {
        $ownerDetails = $this->identityResolver->getUserById($item->getOwnerId());

        if ($ownerDetails === null) {
            return new Recipients();
        }

        return new Recipients($ownerDetails);
    }
}
