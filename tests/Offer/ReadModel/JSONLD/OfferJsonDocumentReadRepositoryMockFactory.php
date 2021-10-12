<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\ReadModel\JSONLD;

use CultuurNet\UDB3\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;

final class OfferJsonDocumentReadRepositoryMockFactory
{
    private InMemoryDocumentRepository $eventRepository;
    private InMemoryDocumentRepository $placeRepository;
    private OfferJsonDocumentReadRepository $repository;

    public function __construct()
    {
        $this->eventRepository = new InMemoryDocumentRepository();
        $this->placeRepository = new InMemoryDocumentRepository();
        $this->repository = new OfferJsonDocumentReadRepository($this->eventRepository, $this->placeRepository);
    }

    public function expectEventDocument(JsonDocument $document): void
    {
        $this->eventRepository->save($document);
    }

    public function expectPlaceDocument(JsonDocument $document): void
    {
        $this->placeRepository->save($document);
    }

    public function create(): OfferJsonDocumentReadRepository
    {
        return $this->repository;
    }
}
