<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\RDF;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\Serializer\Serializable;
use CultuurNet\UDB3\Deserializer\DeserializerInterface;
use CultuurNet\UDB3\Json;
use InvalidArgumentException;

final class DomainMessageJSONDeserializer implements DeserializerInterface
{
    private string $payloadClass;

    public function __construct(string $payloadClass)
    {
        if (!in_array(Serializable::class, class_implements($payloadClass))) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Class \'%s\' does not implement ' . Serializable::class,
                    $payloadClass
                )
            );
        }

        $this->payloadClass = $payloadClass;
    }

    public function deserialize(string $data): DomainMessage
    {
        try {
            $decoded = Json::decodeAssociatively($data);
        } catch (\JsonException $e) {
            throw new InvalidArgumentException('Invalid JSON');
        }

        return new DomainMessage(
            $decoded['id'],
            (int) $decoded['playhead'],
            Metadata::deserialize($decoded['metadata']),
            $this->payloadClass::deserialize($decoded['payload']),
            DateTime::fromString($decoded['recorded_on'])
        );
    }
}
