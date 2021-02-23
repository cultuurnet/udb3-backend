<?php

namespace CultuurNet\UDB3\Broadway\AMQP;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\Broadway\AMQP\DomainMessage\SpecificationInterface;
use CultuurNet\UDB3\Broadway\AMQP\Message\AMQPMessageFactoryInterface;
use PhpAmqpLib\Channel\AMQPChannel;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class AMQPPublisher implements EventListener
{
    use LoggerAwareTrait;

    /**
     * @var string
     */
    private $exchange;

    /**
     * @var SpecificationInterface
     */
    private $domainMessageSpecification;

    /**
     * @var AMQPChannel
     */
    private $channel;

    /**
     * @var AMQPMessageFactoryInterface
     */
    private $messageFactory;

    public function __construct(
        AMQPChannel $channel,
        string $exchange,
        SpecificationInterface $domainMessageSpecification,
        AMQPMessageFactoryInterface $messageFactory
    ) {
        $this->channel = $channel;
        $this->exchange = $exchange;
        $this->domainMessageSpecification = $domainMessageSpecification;
        $this->messageFactory = $messageFactory;
        $this->logger = new NullLogger();
    }

    /**
     * @inheritdoc
     */
    public function handle(DomainMessage $domainMessage)
    {
        if ($this->domainMessageSpecification->isSatisfiedBy($domainMessage)) {
            $this->publishWithAMQP($domainMessage);
        } else {
            $this->logger->warning(
                'message was skipped by specification ' . get_class($this->domainMessageSpecification)
            );
        }
    }

    /**
     * @param DomainMessage $domainMessage
     */
    private function publishWithAMQP(DomainMessage $domainMessage)
    {
        $payload = $domainMessage->getPayload();
        $eventClass = get_class($payload);
        $this->logger->info("publishing message with event type {$eventClass} to exchange {$this->exchange}");

        $this->channel->basic_publish(
            $this->messageFactory->createAMQPMessage($domainMessage),
            $this->exchange
        );
    }
}
