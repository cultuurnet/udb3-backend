<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Broadway\AMQP\Dummies;

class DummyEventNotSerializable
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $content;

    /**
     * @param string $id
     * @param string $content
     */
    public function __construct($id, $content)
    {
        $this->id = $id;
        $this->content = $content;
    }
}
