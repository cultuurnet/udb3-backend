<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventSourcing\DBAL;

use Broadway\Serializer\Serializable;

final class DummyEvent implements Serializable
{
    private string $id;

    private string $content;

    final public function __construct(string $id, string $content)
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
