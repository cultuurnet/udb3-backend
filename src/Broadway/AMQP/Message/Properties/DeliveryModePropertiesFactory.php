<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Broadway\AMQP\Message\Properties;

use Broadway\Domain\DomainMessage;
use PhpAmqpLib\Message\AMQPMessage;

class DeliveryModePropertiesFactory implements PropertiesFactoryInterface
{
    private int $deliveryMode;

    /**
     *   Use AMQPMessage::DELIVERY_MODE_PERSISTENT or AMQPMessage::DELIVERY_MODE_NON_PERSISTENT
     */
    public function __construct(int $deliveryMode)
    {
        $this->guardDeliveryMode($deliveryMode);

        $this->deliveryMode = $deliveryMode;
    }

    public function createProperties(DomainMessage $domainMessage): array
    {
        return ['delivery_mode' => $this->deliveryMode];
    }

    /**
     * @throws \InvalidArgumentException
     */
    private function guardDeliveryMode(int $deliveryMode)
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
