<?php

namespace CultuurNet\UDB3\Offer;

use CultuurNet\UDB3\Event\ReadModel\DocumentRepositoryInterface;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use ValueObjects\Web\Url;

class LocalOfferReadingService implements OfferReadingServiceInterface
{
    /**
     * @var IriOfferIdentifierFactoryInterface
     */
    private $iriOfferIdentifierFactory;

    /**
     * @var DocumentRepositoryInterface[]
     */
    private $documentRepositories;

    /**
     * @param IriOfferIdentifierFactoryInterface
     */
    public function __construct(IriOfferIdentifierFactoryInterface $iriOfferIdentifierFactory)
    {
        $this->iriOfferIdentifierFactory = $iriOfferIdentifierFactory;
        $this->documentRepositories = [];
    }

    /**
     * @param OfferType $offerType
     * @param DocumentRepositoryInterface $documentRepository
     * @return static
     */
    public function withDocumentRepository(
        OfferType $offerType,
        DocumentRepositoryInterface $documentRepository
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
     * @param OfferType $offerType
     * @return DocumentRepositoryInterface
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
