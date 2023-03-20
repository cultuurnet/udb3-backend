<?php

declare(strict_types=1);

namespace CultuurNet\UDB3;

use SimpleXMLElement;

final class SerializableXML extends SimpleXmlElement
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
             * @var SerializableXML $child
             */
            foreach ($this as $tag => $child) {
                $temp = $child->serialize();
                $attributes = [];

                foreach ($child->attributes() as $name => $value) {
                    $attributes["$name"] = (string) $value;
                }
                if (count($attributes) > 0) {
                    $temp = array_merge($temp, [self::ATTRIBUTE_INDEX => $attributes]);
                }

                $array[$tag][] = $temp;
            }
        } else {
            // serialize attributes and text for a leaf-elements
            $temp = trim((string) $this);

            // if only contains empty string, it is actually an empty element
            if ($temp !== '') {
                $array[self::CONTENT_NAME] = $temp;
            }
        }

        if ($this->xpath('/*') == [$this]) {
            $array = [$this->getName() => $array];
        }

        return $array;
    }
}
