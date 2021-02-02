<?php

namespace CultuurNet\UDB3\Organizer;

use Broadway\Serializer\SerializableInterface;

class OrganizerProjectedToJSONLD implements SerializableInterface
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $iri;

    public function __construct(string $id, string $iri)
    {
        $this->id = $id;
        $this->iri = $iri;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getIri(): string
    {
        return $this->iri;
    }

    public function serialize(): array
    {
        return [
            'id' => $this->getId(),
            'iri' => $this->getIri(),
        ];
    }

    public static function deserialize(array $data): OrganizerProjectedToJSONLD
    {
        return new self($data['id'], $data['iri']);
    }
}
