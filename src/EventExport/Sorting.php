<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport;

use CultuurNet\UDB3\Deserializer\MissingValueException;

final class Sorting
{
    private string $property;
    private string $order;

    public function __construct(string $property, string $order)
    {
        $this->property = $property;
        $this->order = $order;
    }

    public function getProperty(): string
    {
        return $this->property;
    }

    public function getOrder(): string
    {
        return $this->order;
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            $this->getProperty() => $this->getOrder(),
        ];
    }

    /**
     * @param object|object[] $json
     */
    public static function fromJson($json): ?Sorting
    {
        $hasProperty = isset($json->sort->property);
        $hasOrder = isset($json->sort->order);

        if ($hasProperty && !$hasOrder) {
            throw new MissingValueException("order is incomplete. You should provide a 'order' key.");
        }

        if (!$hasProperty && $hasOrder) {
            throw new MissingValueException("order is incomplete. You should provide a 'property' key.");
        }

        if ($hasProperty && $hasOrder) { // @phpstan-ignore-line
            return new Sorting(
                $json->sort->property,
                $json->sort->order,
            );
        }

        return null;
    }
}
