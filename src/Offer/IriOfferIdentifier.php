<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer;

use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;

class IriOfferIdentifier implements \JsonSerializable, \Serializable
{
    private Url $iri;

    private string $id;

    private OfferType $type;

    public function __construct(
        Url $iri,
        string $id,
        OfferType $type
    ) {
        $this->iri = $iri;
        $this->type = $type;
        $this->id = $id;
    }

    public function getIri(): Url
    {
        return $this->iri;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getType(): OfferType
    {
        return $this->type;
    }

    public function jsonSerialize(): array
    {
        return [
            '@id' => $this->iri->toString(),
            '@type' => $this->type->toString(),
        ];
    }

    public function serialize(): string
    {
        return Json::encode(
            [
                'iri' => $this->iri->toString(),
                'id' => $this->id,
                'type' => $this->type->toString(),
            ]
        );
    }

    /**
     * @param string $serialized
     */
    public function unserialize($serialized): void
    {
        $data = Json::decodeAssociatively($serialized);
        $this->iri = new Url($data['iri']);
        $this->id = $data['id'];
        $this->type = new OfferType($data['type']);
    }
}
