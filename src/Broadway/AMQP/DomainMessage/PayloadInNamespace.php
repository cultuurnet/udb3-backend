<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Broadway\AMQP\DomainMessage;

use Broadway\Domain\DomainMessage;

class PayloadInNamespace implements SpecificationInterface
{
    private string $namespace;

    public function __construct(string $namespace)
    {
        $namespace = $this->appendNamespaceSeparator($namespace);
        $this->namespace = $namespace;
    }

    public function isSatisfiedBy(DomainMessage $domainMessage): bool
    {
        $payload = $domainMessage->getPayload();

        return 0 === stripos(get_class($payload), $this->namespace);
    }

    private function appendNamespaceSeparator(string $namespace): string
    {
        if (substr($namespace, -1, 1) !== '\\') {
            $namespace .= '\\';
        }

        return $namespace;
    }
}
