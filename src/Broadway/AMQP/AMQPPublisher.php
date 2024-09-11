<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Broadway\AMQP;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListener;
use Closure;
use CultuurNet\UDB3\Broadway\AMQP\DomainMessage\SpecificationInterface;
use CultuurNet\UDB3\Broadway\AMQP\Message\AMQPMessageFactoryInterface;
use PhpAmqpLib\Channel\AMQPChannel;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class AMQPPublisher implements EventListener
{
    use LoggerAwareTrait;

    private string $exchange;

    private SpecificationInterface $domainMessageSpecification;

    private AMQPChannel $channel;

    private AMQPMessageFactoryInterface $messageFactory;

    private Closure $determineRoutingKey;

    public function __construct(
        AMQPChannel $channel,
        string $exchange,
        SpecificationInterface $domainMessageSpecification,
        AMQPMessageFactoryInterface $messageFactory,
        ?Closure $determineRoutingKey = null
    ) {
        $this->channel = $channel;
        $this->exchange = $exchange;
        $this->domainMessageSpecification = $domainMessageSpecification;
        $this->messageFactory = $messageFactory;
        $this->logger = new NullLogger();
        $this->determineRoutingKey = $determineRoutingKey ?? static fn () => '';
    }

    public function handle(DomainMessage $domainMessage): void
    {
        if ($this->domainMessageSpecification->isSatisfiedBy($domainMessage)) {
            $this->publishWithAMQP($domainMessage);
        } else {
            $this->logger->warning(
                'message was skipped by specification ' . get_class($this->domainMessageSpecification)
            );
        }
    }


    private function publishWithAMQP(DomainMessage $domainMessage): void
    {
        $payload = $domainMessage->getPayload();
        $eventClass = get_class($payload);
        $this->logger->info("publishing message with event type {$eventClass} to exchange {$this->exchange}");

        $this->channel->basic_publish(
            $this->messageFactory->createAMQPMessage($domainMessage),
            $this->exchange,
            ($this->determineRoutingKey)($domainMessage)
        );
    }
}
