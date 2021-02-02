<?php

namespace CultuurNet\UDB3\Offer;

use ValueObjects\Web\Url;

class IriOfferIdentifier implements \JsonSerializable, \Serializable
{
    /**
     * @var Url
     */
    private $iri;

    /**
     * @var string
     */
    private $id;

    /**
     * @var OfferType
     */
    private $type;

    /**
     * @param Url $iri
     * @param string $id
     * @param OfferType $type
     */
    public function __construct(
        Url $iri,
        $id,
        OfferType $type
    ) {
        $this->iri = $iri;
        $this->type = $type;
        $this->id = $id;
    }

    /**
     * @return Url
     */
    public function getIri()
    {
        return $this->iri;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return OfferType
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            '@id' => (string) $this->iri,
            '@type' => $this->type->toNative(),
        ];
    }

    /**
     * @return string
     */
    public function serialize()
    {
        return json_encode(
            [
                'iri' => (string) $this->iri,
                'id' => $this->id,
                'type' => $this->type->toNative(),
            ]
        );
    }

    /**
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        $data = json_decode($serialized, true);
        $this->iri = Url::fromNative($data['iri']);
        $this->id = $data['id'];
        $this->type = OfferType::fromNative($data['type']);
    }
}
