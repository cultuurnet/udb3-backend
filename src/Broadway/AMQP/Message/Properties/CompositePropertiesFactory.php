<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Broadway\AMQP\Message\Properties;

use Broadway\Domain\DomainMessage;

class CompositePropertiesFactory implements PropertiesFactoryInterface
{
    /**
     * @var PropertiesFactoryInterface[]
     */
    private array $factories;

    public function __construct()
    {
        $this->factories = [];
    }

    /**
     * @return CompositePropertiesFactory
     */
    public function with(PropertiesFactoryInterface $factory)
    {
        $c = clone $this;
        $c->factories[] = $factory;
        return $c;
    }

    /**
     * @return array
     */
    public function createProperties(DomainMessage $domainMessage)
    {
        $properties = [];

        foreach ($this->factories as $factory) {
            $properties = array_merge($properties, $factory->createProperties($domainMessage));
        }

        return $properties;
    }
}
