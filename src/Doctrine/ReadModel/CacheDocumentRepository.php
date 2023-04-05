<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Doctrine\ReadModel;

use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use Doctrine\Common\Cache\Cache;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use RuntimeException;

final class CacheDocumentRepository implements DocumentRepository
{
    use LoggerAwareTrait;

    private Cache $cache;

    private int $millisecondsBetweenRetry;

    public function __construct(Cache $cache, int $millisecondsBetweenRetry = 0)
    {
        $this->cache = $cache;
        $this->millisecondsBetweenRetry = $millisecondsBetweenRetry;
        $this->logger = new NullLogger();
    }

    public function fetch(string $id, bool $includeMetadata = false): JsonDocument
    {
        $value = $this->cache->fetch($id);

        if ($value === false || $value === 'GONE') {
            throw DocumentDoesNotExist::withId($id);
        }

        return new JsonDocument($id, $value);
    }

    public function save(JsonDocument $document, int $attempts = 3): void
    {
        $saved = $this->cache->save($document->getId(), $document->getRawBody(), 0);

        if (!$saved) {
            throw new RuntimeException('Could not save document ' . $document->getId() . ' to cache.');
        }

        $savedDocument = $this->fetch($document->getId());
        if ($savedDocument->getRawBody() !== $document->getRawBody()) {
            $this->logger->log(
                $attempts > 1 ? 'warning' : 'error',
                'Saved document in cache does not match provided document ' . $document->getId() . '. Retry attempts left: ' . $attempts
            );

            if ($attempts > 0) {
                usleep($this->millisecondsBetweenRetry * 1000);
                $this->save($document, $attempts - 1);
            }
        }
    }

    public function remove($id): void
    {
        $this->cache->delete($id);
    }
}
