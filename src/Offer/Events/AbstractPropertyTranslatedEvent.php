<?php

namespace CultuurNet\UDB3\Offer\Events;

use CultuurNet\UDB3\Language;

abstract class AbstractPropertyTranslatedEvent extends AbstractEvent
{
    /**
     * @var Language
     */
    protected $language;

    public function __construct($itemId, Language $language)
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
        return parent::serialize() + array(
            'language' => (string)$this->language->getCode(),
        );
    }
}
