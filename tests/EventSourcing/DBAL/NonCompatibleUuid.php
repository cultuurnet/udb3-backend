<?php

namespace CultuurNet\UDB3\EventSourcing\DBAL;

class NonCompatibleUuid
{
    /**
     * @var string
     */
    private $uuid;

    /**
     * DummyUuid constructor.
     * @param string $uuid
     */
    public function __construct($uuid)
    {
        $this->uuid = $uuid;
    }
}
