<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\Event\ReadModel;

use CultuurNet\UDB3\ReadModel\JsonDocument;

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
