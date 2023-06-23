<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Broadway\AMQP\DomainMessage;

use Broadway\Domain\DomainMessage;
use InvalidArgumentException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class PayloadIsInstanceOf implements SpecificationInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private string $typeName;

    public function __construct(string $typeName)
    {
        if (!is_string($typeName)) {
            throw new InvalidArgumentException('Value for argument typeName should be a string');
        }
        $this->typeName = $typeName;
        $this->logger = new NullLogger();
    }

    public function isSatisfiedBy(DomainMessage $domainMessage): bool
    {
        $payload = $domainMessage->getPayload();

        $payloadClass = get_class($payload);
        $this->logger->info(
            "expected: {$this->typeName}, actual: {$payloadClass}"
        );

        $satisfied =
            is_a($payload, $this->typeName) ||
            is_subclass_of($payload, $this->typeName);

        $this->logger->info('satisfied: ' . ($satisfied ? 'yes' : 'no'));

        return $satisfied;
    }
}
