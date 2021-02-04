<?php

namespace CultuurNet\BroadwayAMQP\Message\Body;

use Broadway\Domain\DomainMessage;
use Broadway\Serializer\SerializableInterface;
use Broadway\Serializer\SerializationException;

class EntireDomainMessageBodyFactory implements BodyFactoryInterface
{
    /**
     * @inheritdoc
     */
    public function createBody(DomainMessage $domainMessage)
    {
        $this->guardSerializable($domainMessage->getMetadata());
        $this->guardSerializable($domainMessage->getPayload());

        $data = [
            'id' => $domainMessage->getId(),
            'playhead' => $domainMessage->getPlayhead(),
            'metadata' => $domainMessage->getMetadata()->serialize(),
            'payload' => $domainMessage->getPayload()->serialize(),
            'recorded_on' => $domainMessage->getRecordedOn()->toString()
        ];

        return json_encode($data);
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
