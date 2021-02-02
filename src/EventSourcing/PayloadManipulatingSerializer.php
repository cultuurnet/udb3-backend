<?php

namespace CultuurNet\UDB3\EventSourcing;

use Assert\Assertion;
use Broadway\Serializer\SerializerInterface;
use Broadway\Serializer\SimpleInterfaceSerializer;

/**
 * Decorates a SimpleInterfaceSerializer, first maps old class names to new
 * class names.
 */
final class PayloadManipulatingSerializer implements SerializerInterface
{
    /**
     * @var callable[]
     */
    private $manipulations;

    /**
     * @var SimpleInterfaceSerializer
     */
    private $serializer;

    public function __construct(SimpleInterfaceSerializer $serializer)
    {
        $this->serializer = $serializer;
    }

    public function serialize($object): array
    {
        return $this->serializer->serialize($object);
    }

    public function manipulateEventsOfClass(string $className, callable $callback): void
    {
        if (isset($this->manipulations[$className])) {
            throw new \RuntimeException(
                "Manipulation on events of class {$className} already added, " .
                "can add only one."
            );
        }
        $this->manipulations[$className] = $callback;
    }

    public function deserialize(array $serializedObject)
    {
        $manipulatedSerializedObject = $this->manipulate($serializedObject);

        return $this->serializer->deserialize($manipulatedSerializedObject);
    }

    private function manipulate(array $serializedObject): array
    {
        Assertion::keyExists(
            $serializedObject,
            'class',
            "Key 'class' should be set."
        );

        $manipulatedSerializedObject = $serializedObject;
        $class = $manipulatedSerializedObject['class'];

        if (isset($this->manipulations[$class])) {
            $manipulatedSerializedObject = $this->manipulations[$class]($manipulatedSerializedObject);
        }

        return $manipulatedSerializedObject;
    }
}
