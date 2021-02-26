<?php

namespace CultuurNet\UDB3\Broadway\AMQP\Message\Properties;

use Broadway\Domain\DomainMessage;
use PhpAmqpLib\Message\AMQPMessage;

class DeliveryModePropertiesFactory implements PropertiesFactoryInterface
{
    /**
     * @var int
     */
    private $deliveryMode;

    /**
     * @param int $deliveryMode
     *   Use AMQPMessage::DELIVERY_MODE_PERSISTENT or AMQPMessage::DELIVERY_MODE_NON_PERSISTENT
     */
    public function __construct($deliveryMode)
    {
        $this->guardDeliveryMode($deliveryMode);

        $this->deliveryMode = $deliveryMode;
    }

    /**
     * @return array
     */
    public function createProperties(DomainMessage $domainMessage)
    {
        return ['delivery_mode' => $this->deliveryMode];
    }

    /**
     * @param string $deliveryMode
     * @throws \InvalidArgumentException
     */
    private function guardDeliveryMode($deliveryMode)
    {
        $validModes = [
            AMQPMessage::DELIVERY_MODE_NON_PERSISTENT,
            AMQPMessage::DELIVERY_MODE_PERSISTENT,
        ];

        if (!in_array($deliveryMode, $validModes)) {
            throw new \InvalidArgumentException("Invalid amqp delivery mode {$deliveryMode}.");
        }
    }
}
