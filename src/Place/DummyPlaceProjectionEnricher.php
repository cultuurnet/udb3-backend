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
    private $dummyLocationIds = [];

    public function __construct(
        DocumentRepositoryInterface $repository,
        array $dummyLocationIds
    ) {
        $this->repository = $repository;
        $this->dummyLocationIds = $dummyLocationIds;
    }

    public function get($id)
    {
        $readModel = $this->repository->get($id);
        foreach ($this->dummyLocationIds as $dummyLocationId) {
            $body = $readModel->getBody();
            if (strpos($body->place->{'@id'}, $dummyLocationId) !== false) {
                $body->isDummyLocationForEducationEvents = true;
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
