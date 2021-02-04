<?php

namespace CultuurNet\UDB3\Broadway\AMQP\Message;

use Broadway\Domain\DomainMessage;
use CultuurNet\UDB3\Broadway\AMQP\Message\Body\BodyFactoryInterface;
use CultuurNet\UDB3\Broadway\AMQP\Message\Properties\PropertiesFactoryInterface;
use PhpAmqpLib\Message\AMQPMessage;

class DelegatingAMQPMessageFactory implements AMQPMessageFactoryInterface
{
    /**
     * @var BodyFactoryInterface
     */
    private $bodyFactory;

    /**
     * @var PropertiesFactoryInterface
     */
    private $propertiesFactory;

    /**
     * @param BodyFactoryInterface $bodyFactory
     * @param PropertiesFactoryInterface $propertiesFactory
     */
    public function __construct(
        BodyFactoryInterface $bodyFactory,
        PropertiesFactoryInterface $propertiesFactory
    ) {
        $this->bodyFactory = $bodyFactory;
        $this->propertiesFactory = $propertiesFactory;
    }

    /**
     * @param DomainMessage $domainMessage
     * @return AMQPMessage
     */
    public function createAMQPMessage(DomainMessage $domainMessage)
    {
        return new AMQPMessage(
            $this->bodyFactory->createBody($domainMessage),
            $this->propertiesFactory->createProperties($domainMessage)
        );
    }
}
