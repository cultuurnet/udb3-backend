<?php

namespace CultuurNet\UDB3\Offer\Events;

use CultuurNet\UDB3\Description;
use CultuurNet\UDB3\Language;

class AbstractDescriptionTranslated extends AbstractPropertyTranslatedEvent
{
    /**
     * @var Description
     */
    protected $description;

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
        return parent::serialize() + array(
            'description' => $this->description->toNative(),
        );
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
