<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Events;

use CultuurNet\UDB3\Model\ValueObject\Translation\Language;

abstract class AbstractDescriptionDeleted extends AbstractEvent
{
    protected Language $language;

    final public function __construct(string $id, Language $language)
    {
        parent::__construct($id);
        $this->language = $language;
    }

    public function getLanguage(): Language
    {
        return $this->language;
    }

    public function serialize(): array
    {
        return parent::serialize() + [
                'language' => $this->language->toString(),
            ];
    }

    public static function deserialize(array $data): AbstractDescriptionDeleted
    {
        return new static(
            $data['item_id'],
            new Language($data['language'])
        );
    }
}
