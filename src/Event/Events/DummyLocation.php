<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use CultuurNet\UDB3\Model\ValueObject\Geography\Address;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;

final class DummyLocation
{
    private Title $title;

    private Address $address;

    public function __construct(Title $title, Address $address)
    {
        $this->title = $title;
        $this->address = $address;
    }

    public function getTitle(): Title
    {
        return $this->title;
    }

    public function getAddress(): Address
    {
        return $this->address;
    }
}
