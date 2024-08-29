<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Container;

use League\Container\Container;
use League\Container\Definition\DefinitionInterface;
use League\Container\DefinitionContainerInterface;
use League\Container\Inflector\InflectorInterface;
use League\Container\ServiceProvider\ServiceProviderInterface;
use Opis\Closure\SerializableClosure;
use Psr\SimpleCache\CacheInterface;
use function Opis\Closure\unserialize;
use function Opis\Closure\serialize;

final class CachedContainer implements DefinitionContainerInterface
{
    private const CACHE_KEY = 'container';
    private DefinitionContainerInterface $container;

    public function __construct(callable $builder, CacheInterface $cache)
    {
        $cachedContainerBuilder = $cache->get(self::CACHE_KEY);

        if ($cachedContainerBuilder !== null) {
            $unserializedBuilder = unserialize($cachedContainerBuilder);
            $this->container = $unserializedBuilder();
        } else {
            $buildContainer = $builder(new Container());
            $this->container = $buildContainer;
            $serializedBuilder = serialize(
                new SerializableClosure(
                    fn () => $builder(new Container())
                )
            );
            $cache->set(self::CACHE_KEY, $serializedBuilder);
        }
    }

    public function get(string $id)
    {
        return $this->container->get($id);
    }

    public function has(string $id)
    {
        return $this->container->has($id);
    }

    public function add(string $id, $concrete = null): DefinitionInterface
    {
        return $this->container->add($id, $concrete);
    }

    public function addServiceProvider(ServiceProviderInterface $provider): DefinitionContainerInterface
    {
        return $this->container->addServiceProvider($provider);
    }

    public function addShared(string $id, $concrete = null): DefinitionInterface
    {
        return $this->container->addShared($id, $concrete);
    }

    public function extend(string $id): DefinitionInterface
    {
        return $this->container->extend($id);
    }

    public function getNew($id)
    {
        return $this->container->getNew($id);
    }

    public function inflector(string $type, callable $callback = null): InflectorInterface
    {
        return $this->container->inflector($type, $callback);
    }
}
