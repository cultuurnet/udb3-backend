<?php

namespace CultuurNet\BroadwayAMQP\DomainMessage;

use Broadway\Domain\DomainMessage;

class PayloadInNamespace implements SpecificationInterface
{
    /**
     * @var string
     */
    private $namespace;

    /**
     * @param string $namespace
     */
    public function __construct($namespace)
    {
        $namespace = $this->appendNamespaceSeparator($namespace);
        $this->namespace = $namespace;
    }

    /**
     * @inheritdoc
     */
    public function isSatisfiedBy(DomainMessage $domainMessage)
    {
        $payload = $domainMessage->getPayload();

        return 0 === stripos(get_class($payload), $this->namespace);
    }

    /**
     * @param string $namespace
     * @return string
     */
    private function appendNamespaceSeparator($namespace)
    {
        if (substr($namespace, -1, 1) !== '\\') {
            $namespace .= '\\';
        }

        return $namespace;
    }
}
