<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Events;

use CultuurNet\UDB3\Model\ValueObject\Text\Description;

abstract class AbstractDescriptionUpdated extends AbstractEvent
{
    protected Description $description;

    final public function __construct(string $id, Description $description)
    {
        parent::__construct($id);
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

    public static function deserialize(array $data): AbstractDescriptionUpdated
    {
        return new static($data['item_id'], new Description($data['description']));
    }
}
