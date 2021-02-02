<?php

namespace CultuurNet\UDB3\EventSourcing\DBAL;

use Broadway\Serializer\SerializableInterface;

final class DummyEvent implements SerializableInterface
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $content;

    final public function __construct($id, $content)
    {
        $this->id = $id;
        $this->content = $content;
    }

    public static function deserialize(array $data): DummyEvent
    {
        return new static(
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
