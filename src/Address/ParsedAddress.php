<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Address;

final class ParsedAddress
{
    private ?string $thoroughfare;
    private ?string $houseNumber;
    private ?string $postalCode;
    private string $municipality;

    public function __construct(?string $thoroughfare, ?string $houseNumber, ?string $postalCode, string $municipality)
    {
        $this->thoroughfare = $thoroughfare;
        $this->houseNumber = $houseNumber;
        $this->postalCode = $postalCode;
        $this->municipality = $municipality;
    }

    public function getThoroughfare(): ?string
    {
        return $this->thoroughfare;
    }

    public function getHouseNumber(): ?string
    {
        return $this->houseNumber;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function getMunicipality(): string
    {
        return $this->municipality;
    }
}
