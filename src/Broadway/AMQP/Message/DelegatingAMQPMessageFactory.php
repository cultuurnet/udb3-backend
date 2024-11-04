<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Broadway\AMQP\Message;

use Broadway\Domain\DomainMessage;
use CultuurNet\UDB3\Broadway\AMQP\Message\Body\BodyFactoryInterface;
use CultuurNet\UDB3\Broadway\AMQP\Message\Properties\PropertiesFactoryInterface;
use PhpAmqpLib\Message\AMQPMessage;

class DelegatingAMQPMessageFactory implements AMQPMessageFactoryInterface
{
    private BodyFactoryInterface $bodyFactory;

    private PropertiesFactoryInterface $propertiesFactory;


    public function __construct(
        BodyFactoryInterface $bodyFactory,
        PropertiesFactoryInterface $propertiesFactory
    ) {
        $this->bodyFactory = $bodyFactory;
        $this->propertiesFactory = $propertiesFactory;
    }

    public function createAMQPMessage(DomainMessage $domainMessage): AMQPMessage
    {
        return new AMQPMessage(
            $this->bodyFactory->createBody($domainMessage),
            $this->propertiesFactory->createProperties($domainMessage)
        );
    }
}
