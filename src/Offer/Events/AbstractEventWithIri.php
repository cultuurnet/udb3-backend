<?php

namespace CultuurNet\UDB3\Offer\Events;

abstract class AbstractEventWithIri extends AbstractEvent
{
    /**
     * @var string
     */
    private $iri;

    final public function __construct(string $itemId, $iri)
    {
        parent::__construct($itemId);
        $this->iri = (string) $iri;
    }

    public function getIri(): string
    {
        return $this->iri;
    }

    public function serialize(): array
    {
        return parent::serialize() + array(
            'iri' => $this->iri,
        );
    }

    public static function deserialize(array $data): AbstractEventWithIri
    {
        return new static($data['item_id'], $data['iri']);
    }
}
