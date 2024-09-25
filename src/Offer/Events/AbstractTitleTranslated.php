<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Events;

use CultuurNet\UDB3\Model\ValueObject\Translation\Language;

abstract class AbstractTitleTranslated extends AbstractPropertyTranslatedEvent
{
    protected string $title;

    final public function __construct(string $itemId, Language $language, string $title)
    {
        parent::__construct($itemId, $language);
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

    public static function deserialize(array $data): AbstractTitleTranslated
    {
        return new static(
            $data['item_id'],
            new Language($data['language']),
            $data['title']
        );
    }
}
