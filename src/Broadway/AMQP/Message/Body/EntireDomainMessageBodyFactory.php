<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Broadway\AMQP\Message\Body;

use Broadway\Domain\DomainMessage;
use Broadway\Serializer\Serializable;
use Broadway\Serializer\SerializationException;
use CultuurNet\UDB3\Json;

class EntireDomainMessageBodyFactory implements BodyFactoryInterface
{
    public function createBody(DomainMessage $domainMessage): string
    {
        $this->guardSerializable($domainMessage->getMetadata());
        $this->guardSerializable($domainMessage->getPayload());

        $data = [
            'id' => $domainMessage->getId(),
            'playhead' => $domainMessage->getPlayhead(),
            'metadata' => $domainMessage->getMetadata()->serialize(),
            'payload' => $domainMessage->getPayload()->serialize(),
            'recorded_on' => $domainMessage->getRecordedOn()->toString(),
        ];

        return Json::encode($data);
    }

    /**
     * @throws SerializationException
     */
    private function guardSerializable(object $object): void
    {
        if (!$object instanceof Serializable) {
            throw new SerializationException(
                'Unable to serialize ' . get_class($object)
            );
        }
    }
}
