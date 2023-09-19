<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Events;

use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use CultuurNet\UDB3\Title as LegacyTitle;

abstract class AbstractTitleTranslated extends AbstractPropertyTranslatedEvent
{
    protected Title $title;

    final public function __construct(string $itemId, Language $language, Title $title)
    {
        parent::__construct($itemId, $language);
        $this->title = $title;
    }

    public function getTitle(): LegacyTitle
    {
        //return $this->title;
        return LegacyTitle::fromUdb3ModelTitle($this->title);
    }

    public function serialize(): array
    {
        return parent::serialize() + [
            'title' => $this->title->toString(),
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
