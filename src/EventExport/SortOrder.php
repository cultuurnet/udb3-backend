<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport;

final class SortOrder
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

}