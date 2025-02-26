<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Mailer\Ownership\RecipientStrategy;

use CultuurNet\UDB3\Ownership\Repositories\OwnershipItem;
use CultuurNet\UDB3\User\UserIdentityDetails;
use CultuurNet\UDB3\User\UserIdentityResolver;
use Psr\Log\LoggerInterface;

class SendToOwnersOfOrganisation implements RecipientStrategy
{
    private UserIdentityResolver $identityResolver;
    private LoggerInterface $logger;

    public function __construct(UserIdentityResolver $identityResolver, LoggerInterface $logger)
    {
        $this->identityResolver = $identityResolver;
        $this->logger = $logger;
    }

    /** @return UserIdentityDetails[] */
    public function getRecipients(OwnershipItem $item, array $organizer): array
    {
        //@todo loop over ALL owners of organisation
        $ownerDetails = $this->identityResolver->getUserById($organizer['creator'] ?? '');

        if ($ownerDetails === null) {
            $this->logger->warning(sprintf('[ownership-mail] Could not load owner details for %s', (empty($organizer['creator']) ? 'unknown' : $organizer['creator'])));
            return [];
        }

        return [$ownerDetails];
    }
}
