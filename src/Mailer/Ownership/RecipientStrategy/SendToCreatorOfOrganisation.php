<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Mailer\Ownership\RecipientStrategy;

use CultuurNet\UDB3\Ownership\Repositories\OwnershipItem;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\User\Recipients;
use CultuurNet\UDB3\User\UserIdentityResolver;

class SendToCreatorOfOrganisation implements RecipientStrategy
{
    private UserIdentityResolver $identityResolver;
    private DocumentRepository $organizerRepository;

    public function __construct(UserIdentityResolver $identityResolver, DocumentRepository $organizerRepository)
    {
        $this->identityResolver = $identityResolver;
        $this->organizerRepository = $organizerRepository;
    }

    public function getRecipients(OwnershipItem $item): Recipients
    {
        try {
            $organizer = $this->organizerRepository->fetch($item->getItemId())->getAssocBody();
        } catch (DocumentDoesNotExist $e) {
            return new Recipients();
        }

        if (empty($organizer['creator'])) {
            return new Recipients();
        }

        $ownerDetails = $this->identityResolver->getUserById($organizer['creator']);

        if ($ownerDetails === null) {
            /*
             Known issue: Currently, we are not addressing this, but it could be resolved later by fetching contacts via an API endpoint in Publiq Platform.
             An Organizer can be created using a clientId instead of a userId, and clients do not have an emailAddress to send the OwnershipRequest to.
             In the short term, this is unlikely to cause major issues since clientIds were introduced recently and are not as frequently created as events or places.
             However, eventually, a user will request ownership of an Organizer created with a clientId.
            While we have client contact details in Publiq Platform, they are not available in UDB. We could create an endpoint in Platform to fetch those contacts.
            */
            return new Recipients();
        }

        return new Recipients($ownerDetails);
    }
}
