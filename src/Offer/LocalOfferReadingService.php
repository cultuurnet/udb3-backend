<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer;

use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use ValueObjects\Web\Url;

class LocalOfferReadingService implements OfferReadingServiceInterface
{
    /**
     * @var IriOfferIdentifierFactoryInterface
     */
    private $iriOfferIdentifierFactory;

    /**
     * @var DocumentRepository[]
     */
    private $documentRepositories;

    public function __construct(IriOfferIdentifierFactoryInterface $iriOfferIdentifierFactory)
    {
        $this->iriOfferIdentifierFactory = $iriOfferIdentifierFactory;
        $this->documentRepositories = [];
    }

    /**
     * @return static
     */
    public function withDocumentRepository(
        OfferType $offerType,
        DocumentRepository $documentRepository
    ) {
        $c = clone $this;
        $c->documentRepositories[$offerType->toNative()] = $documentRepository;
        return $c;
    }

    /**
     * @param string $iri
     * @return JsonDocument
     */
    public function load($iri)
    {
        $identifier = $this->iriOfferIdentifierFactory->fromIri(
            Url::fromNative($iri)
        );

        $id = $identifier->getId();
        $type = $identifier->getType();

        $repository = $this->getDocumentRepository($type);

        return $repository->get($id);
    }

    /**
     * @return DocumentRepository
     */
    private function getDocumentRepository(OfferType $offerType)
    {
        $offerType = $offerType->toNative();

        if (!isset($this->documentRepositories[$offerType])) {
            throw new \LogicException("No document repository found for offer type {$offerType}.");
        }

        return $this->documentRepositories[$offerType];
    }
}
