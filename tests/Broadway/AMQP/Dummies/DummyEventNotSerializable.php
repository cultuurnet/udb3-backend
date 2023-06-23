<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Broadway\AMQP\Dummies;

class DummyEventNotSerializable
{
    protected string $id;

    protected string $content;

    public function __construct(string $id, string $content)
    {
        $this->id = $id;
        $this->content = $content;
    }
}
