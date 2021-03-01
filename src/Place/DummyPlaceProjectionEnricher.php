<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place;

use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;

class DummyPlaceProjectionEnricher implements DocumentRepository
{
    /**
     * @var DocumentRepository
     */
    private $repository;

    /**
     * @var string[]
     */
    private $dummyPlaceIds;

    public function __construct(
        DocumentRepository $repository,
        array $dummyPlaceIds
    ) {
        $this->repository = $repository;
        $this->dummyPlaceIds = $dummyPlaceIds;
    }

    public function fetch(string $id, bool $includeMetadata = false): JsonDocument
    {
        return $this->enrich(
            $this->repository->fetch($id)
        );
    }

    public function get(string $id, bool $includeMetadata = false): ?JsonDocument
    {
        $readModel = $this->repository->get($id, $includeMetadata);
        if (!$readModel) {
            return $readModel;
        }

        return $this->enrich($readModel);
    }

    public function save(JsonDocument $readModel): void
    {
        $this->repository->save($readModel);
    }

    public function remove($id): void
    {
        $this->repository->remove($id);
    }

    private function enrich(JsonDocument $readModel): JsonDocument
    {
        foreach ($this->dummyPlaceIds as $dummyPlaceId) {
            $body = $readModel->getBody();
            if (strpos($body->{'@id'}, $dummyPlaceId) !== false) {
                $body->isDummyPlaceForEducationEvents = true;
                return $readModel->withBody($body);
            }
        }
        return $readModel;
    }
}
