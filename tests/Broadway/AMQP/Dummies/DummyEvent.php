<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Broadway\AMQP\Dummies;

use Broadway\Serializer\Serializable;

class DummyEvent implements Serializable
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

    /**
     * @return mixed The object instance
     */
    public static function deserialize(array $data)
    {
        return new self(
            $data['id'],
            $data['content']
        );
    }

    public function serialize(): array
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
        ];
    }
}
