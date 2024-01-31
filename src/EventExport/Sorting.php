<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport;

use CultuurNet\UDB3\Deserializer\MissingValueException;

final class Sorting
{
    public const DEFAULT_ORDER = 'asc';
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
        $property = $json->sort->property ?? null;
        $order = $json->sort->order ?? null;

        if ($property === null && $order === null) {
            return null;
        }

        if ($property === null) {
            throw new MissingValueException("order is incomplete. You should provide a 'property' key.");
        }

        return new Sorting(
            $property,
            $order ?? self::DEFAULT_ORDER,
        );
    }
}
