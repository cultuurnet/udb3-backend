<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Mailer\Ownership\RecipientStrategy;

use CultuurNet\UDB3\Ownership\Repositories\OwnershipItem;
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
        $organizer = $this->organizerRepository->fetch($item->getItemId())->getAssocBody();

        if (empty($organizer['creator'])) {
            return new Recipients();
        }

        $ownerDetails = $this->identityResolver->getUserById($organizer['creator']);

        if ($ownerDetails === null) {
            return new Recipients();
        }

        return new Recipients($ownerDetails);
    }
}
