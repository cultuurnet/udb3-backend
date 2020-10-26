<?php

namespace CultuurNet\UDB3\ReadModel;

use CultuurNet\UDB3\Event\ReadModel\DocumentGoneException;

class InMemoryDocumentRepository implements DocumentRepositoryInterface
{
    /**
     * @var JsonDocument[]
     */
    private $documents;

    /**
     * @inheritdoc
     */
    public function get($id)
    {
        if (isset($this->documents[$id])) {
            if ('GONE' === $this->documents[$id]) {
                throw new DocumentGoneException();
            }

            return $this->documents[$id];
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function save(JsonDocument $readModel)
    {
        $this->documents[$readModel->getId()] = $readModel;
    }

    /**
     * @inheritdoc
     */
    public function remove($id)
    {
        $this->documents[$id] = 'GONE';
    }
}
