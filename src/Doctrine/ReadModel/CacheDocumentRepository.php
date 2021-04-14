<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Doctrine\ReadModel;

use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use Doctrine\Common\Cache\Cache;

class CacheDocumentRepository implements DocumentRepository
{
    protected $cache;

    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    public function fetch(string $id, bool $includeMetadata = false): JsonDocument
    {
        $value = $this->cache->fetch($id);

        if ($value === 'GONE') {
            throw DocumentDoesNotExist::gone($id);
        }

        if ($value === false) {
            throw DocumentDoesNotExist::notFound($id);
        }

        return new JsonDocument($id, $value);
    }

    public function save(JsonDocument $document): void
    {
        $this->cache->save($document->getId(), $document->getRawBody(), 0);
    }

    public function remove($id): void
    {
        $this->cache->save($id, 'GONE', 0);
    }
}
