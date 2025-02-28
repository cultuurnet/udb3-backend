<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Mailer\Ownership\RecipientStrategy;

use CultuurNet\UDB3\Ownership\Repositories\OwnershipItem;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\User\UserIdentityDetails;
use CultuurNet\UDB3\User\UserIdentityResolver;
use Psr\Log\LoggerInterface;

class SendToOwnersOfOrganisation implements RecipientStrategy
{
    private UserIdentityResolver $identityResolver;
    private DocumentRepository $organizerRepository;
    private LoggerInterface $logger;

    public function __construct(UserIdentityResolver $identityResolver, DocumentRepository $organizerRepository, LoggerInterface $logger)
    {
        $this->identityResolver = $identityResolver;
        $this->organizerRepository = $organizerRepository;
        $this->logger = $logger;
    }

    /**
     * @return UserIdentityDetails[]
     * @throws DocumentDoesNotExist
     */
    public function getRecipients(OwnershipItem $item): array
    {
        $organizer = $this->organizerRepository->fetch($item->getItemId())->getAssocBody();

        //@todo loop over ALL owners of organisation
        $ownerDetails = $this->identityResolver->getUserById($organizer['creator'] ?? '');

        //check all ownerships

        //check other roles

        //make sure nobody gets email double

        if ($ownerDetails === null) {
            $this->logger->warning(sprintf('[ownership-mail] Could not load owner details for %s', (empty($organizer['creator']) ? 'unknown' : $organizer['creator'])));
            return [];
        }

        return [$ownerDetails];
    }
}
