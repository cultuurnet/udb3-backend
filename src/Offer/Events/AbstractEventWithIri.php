<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Events;

abstract class AbstractEventWithIri extends AbstractEvent
{
    private string $iri;

    final public function __construct(string $itemId, string $iri)
    {
        parent::__construct($itemId);
        $this->iri = $iri;
    }

    public function getIri(): string
    {
        return $this->iri;
    }

    public function serialize(): array
    {
        return parent::serialize() + [
            'iri' => $this->iri,
        ];
    }

    public static function deserialize(array $data): AbstractEventWithIri
    {
        return new static($data['item_id'], $data['iri']);
    }
}
