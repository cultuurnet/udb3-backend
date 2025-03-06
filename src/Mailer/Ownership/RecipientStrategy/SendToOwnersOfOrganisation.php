<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Mailer\Ownership\RecipientStrategy;

use CultuurNet\UDB3\Http\Ownership\Search\SearchParameter;
use CultuurNet\UDB3\Http\Ownership\Search\SearchQuery;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Ownership\OwnershipState;
use CultuurNet\UDB3\Ownership\Repositories\OwnershipItem;
use CultuurNet\UDB3\Ownership\Repositories\Search\OwnershipSearchRepository;
use CultuurNet\UDB3\User\Recipients;
use CultuurNet\UDB3\User\UserIdentityDetails;
use CultuurNet\UDB3\User\UserIdentityResolver;

class SendToOwnersOfOrganisation implements RecipientStrategy
{
    private UserIdentityResolver $identityResolver;
    private OwnershipSearchRepository $ownershipSearchRepository;

    public function __construct(UserIdentityResolver $identityResolver, OwnershipSearchRepository $ownershipSearchRepository)
    {
        $this->identityResolver = $identityResolver;
        $this->ownershipSearchRepository = $ownershipSearchRepository;
    }

    public function getRecipients(OwnershipItem $item): Recipients
    {
        $ownerships = $this->ownershipSearchRepository
            ->search(new SearchQuery([
                new SearchParameter('itemId', $item->getItemId()),
                new SearchParameter('itemType', 'organizer'),
                new SearchParameter('state', OwnershipState::approved()->toString()),
            ]));

        $recipients = new Recipients();

        /** @var OwnershipItem $ownershipItem */
        foreach ($ownerships as $ownershipItem) {
            $recipients->add($this->identityResolver->getUserById($ownershipItem->getOwnerId()));
        }

        return $recipients;
    }
}
