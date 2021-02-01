<?php

namespace CultuurNet\UDB3\Offer\Events;

use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Title;

abstract class AbstractTitleTranslated extends AbstractPropertyTranslatedEvent
{
    /**
     * @var Title
     */
    protected $title;

    final public function __construct(string $itemId, Language $language, Title $title)
    {
        parent::__construct($itemId, $language);
        $this->title = $title;
    }

    public function getTitle(): Title
    {
        return $this->title;
    }

    public function serialize(): array
    {
        return parent::serialize() + [
            'title' => $this->title->toNative(),
        ];
    }

    public static function deserialize(array $data): AbstractTitleTranslated
    {
        return new static(
            $data['item_id'],
            new Language($data['language']),
            new Title($data['title'])
        );
    }
}
