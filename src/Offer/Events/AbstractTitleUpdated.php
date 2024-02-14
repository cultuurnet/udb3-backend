<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Events;

abstract class AbstractTitleUpdated extends AbstractEvent
{
    protected string $title;

    final public function __construct(string $id, string $title)
    {
        parent::__construct($id);
        $this->title = $title;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function serialize(): array
    {
        return parent::serialize() + [
            'title' => $this->title,
        ];
    }

    public static function deserialize(array $data): AbstractTitleUpdated
    {
        return new static($data['item_id'], $data['title']);
    }
}
