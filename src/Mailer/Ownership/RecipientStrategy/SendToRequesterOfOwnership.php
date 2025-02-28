<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Mailer\Ownership\RecipientStrategy;

use CultuurNet\UDB3\Ownership\Repositories\OwnershipItem;
use CultuurNet\UDB3\User\UserIdentityDetails;
use CultuurNet\UDB3\User\UserIdentityResolver;
use Psr\Log\LoggerInterface;

class SendToRequesterOfOwnership implements RecipientStrategy
{
    private UserIdentityResolver $identityResolver;
    private LoggerInterface $logger;

    public function __construct(UserIdentityResolver $identityResolver, LoggerInterface $logger)
    {
        $this->identityResolver = $identityResolver;
        $this->logger = $logger;
    }

    /** @return UserIdentityDetails[] */
    public function getRecipients(OwnershipItem $item): array
    {
        $ownerDetails = $this->identityResolver->getUserById($item->getOwnerId());

        if ($ownerDetails === null) {
            $this->logger->warning(sprintf('[ownership-mail] Could not load owner details for %s', $item->getOwnerId()));
            return [];
        }

        return [$ownerDetails];
    }
}
