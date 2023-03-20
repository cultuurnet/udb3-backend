<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Doctrine\ReadModel;

use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use Doctrine\Common\Cache\Cache;
use Psr\Log\LoggerAwareTrait;
use RuntimeException;

class CacheDocumentRepository implements DocumentRepository
{
    protected $cache;
    use LoggerAwareTrait;

    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    public function fetch(string $id, bool $includeMetadata = false): JsonDocument
    {
        $value = $this->cache->fetch($id);

        if ($value === false || $value === 'GONE') {
            throw DocumentDoesNotExist::withId($id);
        }

        return new JsonDocument($id, $value);
    }

    public function save(JsonDocument $document): void
    {
        $saved = $this->cache->save($document->getId(), $document->getRawBody(), 0);

        if (!$saved) {
            throw new RuntimeException('Could not save document ' . $document->getId() . 'to cache.');
        }
    }

    public function remove($id): void
    {
        $this->cache->delete($id);
    }
}
