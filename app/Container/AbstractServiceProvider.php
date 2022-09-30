<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Container;

use League\Container\ContainerAwareTrait;
use League\Container\ServiceProvider\ServiceProviderInterface;

abstract class AbstractServiceProvider implements ServiceProviderInterface
{
    use ContainerAwareTrait;

    private ?string $identifier = null;

    abstract protected function getProvidedServiceNames(): array;

    final public function provides(string $id): bool
    {
        return in_array($id, $this->getProvidedServiceNames(), true);
    }

    final public function getIdentifier(): string
    {
        return $this->identifier ?? get_class($this);
    }

    final public function setIdentifier(string $id): ServiceProviderInterface
    {
        $this->identifier = $id;
        return $this;
    }
}
