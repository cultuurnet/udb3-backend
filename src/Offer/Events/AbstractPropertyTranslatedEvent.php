<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Events;

use CultuurNet\UDB3\Model\ValueObject\Translation\Language;

abstract class AbstractPropertyTranslatedEvent extends AbstractEvent
{
    protected Language $language;

    public function __construct(string $itemId, Language $language)
    {
        $this->language = $language;
        parent::__construct($itemId);
    }

    public function getLanguage(): Language
    {
        return $this->language;
    }

    public function serialize(): array
    {
        return parent::serialize() + [
            'language' => $this->language->getCode(),
        ];
    }
}
