<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Mailer\Handler\Helper;

/*
 * This class fetches all required params to be sent to the twig template for the Ownership emails
 * */

use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Offer\ExtractOfferName;
use CultuurNet\UDB3\Ownership\Repositories\OwnershipItem;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\User\UserIdentityDetails;

class OwnershipMailParamExtractor
{
    private DocumentRepository $organizerRepository;
    private IriGeneratorInterface $organizerIriGenerator;

    public function __construct(
        DocumentRepository $organizerRepository,
        IriGeneratorInterface $organizerIriGenerator
    ) {
        $this->organizerRepository = $organizerRepository;
        $this->organizerIriGenerator = $organizerIriGenerator;
    }

    /**
     * @throws DocumentDoesNotExist
     */
    public function fetchParams(OwnershipItem $ownershipItem, UserIdentityDetails $identityDetails): array
    {
        $organizer = $this->organizerRepository->fetch($ownershipItem->getItemId())->getAssocBody();

        return [
            'organisationName' => ExtractOfferName::extract($organizer),
            'firstName' => $identityDetails->getUserName(),
            'organisationUrl' => $this->organizerIriGenerator->iri($ownershipItem->getItemId()),
        ];
    }
}
