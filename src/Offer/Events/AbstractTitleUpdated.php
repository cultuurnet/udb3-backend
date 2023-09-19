<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Events;

use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use CultuurNet\UDB3\Title as LegacyTitle;

abstract class AbstractTitleUpdated extends AbstractEvent
{
    protected Title $title;

    final public function __construct(string $id, Title $title)
    {
        parent::__construct($id);
        $this->title = $title;
    }

    public function getTitle(): LegacyTitle
    {
        return LegacyTitle::fromUdb3ModelTitle($this->title);
    }

    public function serialize(): array
    {
        return parent::serialize() + [
            'title' => $this->title->toString(),
        ];
    }

    public static function deserialize(array $data): AbstractTitleUpdated
    {
        return new static($data['item_id'], new Title($data['title']));
    }
}
