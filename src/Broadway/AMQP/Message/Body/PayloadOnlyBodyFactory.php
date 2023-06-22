<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Broadway\AMQP\Message\Body;

use Broadway\Domain\DomainMessage;
use Broadway\Serializer\Serializable;
use Broadway\Serializer\SerializationException;

class PayloadOnlyBodyFactory implements BodyFactoryInterface
{
    public function createBody(DomainMessage $domainMessage): string
    {
        $this->guardSerializable($domainMessage->getPayload());

        return json_encode(
            $domainMessage->getPayload()->serialize()
        );
    }

    /**
     * @throws SerializationException
     */
    private function guardSerializable($object): void
    {
        if (!$object instanceof Serializable) {
            throw new SerializationException(
                'Unable to serialize ' . get_class($object)
            );
        }
    }
}
