<?php

namespace CultuurNet\UDB3\Offer\Events;

use CultuurNet\UDB3\Title;

abstract class AbstractTitleUpdated extends AbstractEvent
{
    /**
     * @var Title
     */
    protected $title;

    final public function __construct(string $id, Title $title)
    {
        parent::__construct($id);
        $this->title = $title;
    }

    public function getTitle(): Title
    {
        return $this->title;
    }

    public function serialize(): array
    {
        return parent::serialize() + array(
            'title' => (string) $this->title,
        );
    }

    public static function deserialize(array $data): AbstractTitleUpdated
    {
        return new static($data['item_id'], new Title($data['title']));
    }
}
