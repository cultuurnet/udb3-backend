<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ownership\Readmodels\Name;

use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Offer\ExtractOfferName;
use CultuurNet\UDB3\ReadModel\DocumentRepository;

final class DocumentItemNameResolver implements ItemNameResolver
{
    private DocumentRepository $organizerDocumentRepository;

    public function __construct(DocumentRepository $organizerDocumentRepository)
    {
        $this->organizerDocumentRepository = $organizerDocumentRepository;
    }

    public function resolve(string $itemId): string
    {
        $organizerDocument = $this->organizerDocumentRepository->fetch($itemId);

        $organizer = Json::decodeAssociatively($organizerDocument->getRawBody());

        return (new ExtractOfferName())->extract($organizer);
    }
}
