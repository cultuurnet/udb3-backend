<?php declare(strict_types=1);

namespace CultuurNet\UDB3\User;

use Doctrine\Common\Cache\Cache;

class CacheRepository implements Auth0ManagementTokenRepository
{
    /**
     * @var Cache
     */
    private $cache;


    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    public function token(): ?string
    {
        if (!$this->cache->contains('token')) {
            return null;
        }
        return $this->cache->fetch('token');
    }

    public function store(string $token): void
    {
        $this->cache->save('token', $token);
    }
}
