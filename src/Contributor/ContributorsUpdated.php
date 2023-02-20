<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Contributor;

use Broadway\Serializer\Serializable;

abstract class ContributorsUpdated implements Serializable
{
    private string $id;

    private string $iri;

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
}
