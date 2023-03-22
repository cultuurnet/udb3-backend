<?php

declare(strict_types=1);

namespace CultuurNet\UDB3;

use SimpleXMLElement;

final class SerializableSimpleXmlElement extends SimpleXmlElement
{
    public const ATTRIBUTE_INDEX = '@attributes';
    public const CONTENT_NAME = '_text';

    public function serialize(): array
    {
        $array = [];

        if ($this->count()) {
            // serialize children if there are children
            /**
             * @var string $tag
             * @var SerializableSimpleXmlElement $child
             */
            foreach ($this as $tag => $child) {
                $serializedChild = $child->serialize();
                $attributes = [];

                // Check if node has attributes and if so add them.
                foreach ($child->attributes() as $name => $value) {
                    $attributes["$name"] = (string) $value;
                }
                if (count($attributes) > 0) {
                    $serializedChild = array_merge($serializedChild, [self::ATTRIBUTE_INDEX => $attributes]);
                }

                // add the node to the array with the tagname as key
                $array[$tag][] = $serializedChild;
            }
        } else {
            // serialize attributes and text for a leaf-elements
            $serialized = trim((string) $this);

            // if only contains empty string, it is actually an empty element
            if ($serialized !== '') {
                $array[self::CONTENT_NAME] = $serialized;
            }
        }

        if ($this->xpath('/*') == [$this]) {
            $array = [$this->getName() => $array];
        }

        return $array;
    }
}
