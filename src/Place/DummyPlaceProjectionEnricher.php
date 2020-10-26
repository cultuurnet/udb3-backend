<?php

namespace CultuurNet\UDB3\Place;

use CultuurNet\UDB3\ReadModel\DocumentRepositoryInterface;
use CultuurNet\UDB3\ReadModel\JsonDocument;

class DummyPlaceProjectionEnricher implements DocumentRepositoryInterface
{
    /**
     * @var DocumentRepositoryInterface
     */
    private $repository;

    /**
     * @var string[]
     */
    private $dummyPlaceIds = [];

    public function __construct(
        DocumentRepositoryInterface $repository,
        array $dummyPlaceIds
    ) {
        $this->repository = $repository;
        $this->dummyPlaceIds = $dummyPlaceIds;
    }

    public function get(string $id, bool $includeMetadata = false): ?JsonDocument
    {
        $readModel = $this->repository->get($id);
        if (!$readModel) {
            return $readModel;
        }

        foreach ($this->dummyPlaceIds as $dummyPlaceId) {
            $body = $readModel->getBody();
            if (strpos($body->{'@id'}, $dummyPlaceId) !== false) {
                $body->isDummyPlaceForEducationEvents = true;
                return $readModel->withBody($body);
            }
        }
        return $readModel;
    }

    public function save(JsonDocument $readModel): void
    {
        $this->repository->save($readModel);
    }

    public function remove($id): void
    {
        $this->repository->remove($id);
    }
}
