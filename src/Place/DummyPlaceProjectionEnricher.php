<?php

namespace CultuurNet\UDB3\Place;

use CultuurNet\UDB3\Event\ReadModel\DocumentRepositoryInterface;
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

    public function get($id)
    {
        $readModel = $this->repository->get($id);
        foreach ($this->dummyPlaceIds as $dummyPlaceId) {
            $body = $readModel->getBody();
            if (strpos($body->place->{'@id'}, $dummyPlaceId) !== false) {
                $body->isDummyPlaceForEducationEvents = true;
                return $readModel->withBody($body);
            }
        }
        return $readModel;
    }

    public function save(JsonDocument $readModel)
    {
        $this->repository->save($readModel);
    }

    public function remove($id)
    {
        $this->repository->remove($id);
    }
}
