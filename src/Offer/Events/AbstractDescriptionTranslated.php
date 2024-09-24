<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Events;

use CultuurNet\UDB3\Description;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;

class AbstractDescriptionTranslated extends AbstractPropertyTranslatedEvent
{
    protected Description $description;

    final public function __construct(string $itemId, Language $language, Description $description)
    {
        parent::__construct($itemId, $language);
        $this->description = $description;
    }

    public function getDescription(): Description
    {
        return $this->description;
    }

    public function serialize(): array
    {
        return parent::serialize() + [
            'description' => $this->description->toString(),
        ];
    }

    public static function deserialize(array $data): AbstractDescriptionTranslated
    {
        return new static(
            $data['item_id'],
            new Language($data['language']),
            new Description($data['description'])
        );
    }
}
