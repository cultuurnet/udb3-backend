<?php

namespace CultuurNet\BroadwayAMQP\Message\Body;

use Broadway\Domain\DomainMessage;
use Broadway\Serializer\SerializableInterface;
use Broadway\Serializer\SerializationException;

class PayloadOnlyBodyFactory implements BodyFactoryInterface
{
    /**
     * @inheritdoc
     */
    public function createBody(DomainMessage $domainMessage)
    {
        $this->guardSerializable($domainMessage->getPayload());

        return json_encode(
            $domainMessage->getPayload()->serialize()
        );
    }

    /**
     * @param mixed $object
     * @throws SerializationException
     */
    private function guardSerializable($object)
    {
        if (!$object instanceof SerializableInterface) {
            throw new SerializationException(
                'Unable to serialize ' . get_class($object)
            );
        }
    }
}
