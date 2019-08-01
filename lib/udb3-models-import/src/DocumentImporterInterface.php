<?php

namespace CultuurNet\UDB3\Model\Import;

use CultuurNet\UDB3\ApiGuard\Consumer\ConsumerInterface;

interface DocumentImporterInterface
{
    /**
     * @param DecodedDocument $decodedDocument
     * @param ConsumerInterface|null $consumer
     */
    public function import(DecodedDocument $decodedDocument, ConsumerInterface $consumer = null);
}
