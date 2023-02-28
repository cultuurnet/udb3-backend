<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\RDF;

use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use Doctrine\Common\Cache\Cache;

final class CacheMainLanguageRepository implements MainLanguageRepository
{
    private Cache $cache;

    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    public function save(string $resourceId, Language $mainLanguage): void
    {
        $this->cache->save($resourceId, $mainLanguage->toString());
    }

    public function get(string $resourceId, Language $default = null): ?Language
    {
        $value = $this->cache->fetch($resourceId);
        if ($value === false) {
            return $default;
        }
        return new Language($value);
    }
}
