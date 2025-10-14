<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\ReadModel\Duplicate;

use CultuurNet\UDB3\Model\ValueObject\Geography\Address;
use Symfony\Component\String\Slugger\AsciiSlugger;

class UniqueAddressIdentifierFactory
{
    private bool $duplicatePlacesPerUser;

    public function __construct(bool $duplicatePlacesPerUser)
    {
        $this->duplicatePlacesPerUser = $duplicatePlacesPerUser;
    }

    public function create(string $title, Address $address, string $currentUserId): string
    {
        $parts = array_map(
            fn ($part) => str_replace(' ', '_', trim($part)),
            $this->getParts($title, $address, $currentUserId)
        );

        return mb_strtolower($this->escapeSpecialCharacters(implode('_', array_filter($parts))));
    }

    private function getParts(string $title, Address $address, string $currentUserId): array
    {
        $parts = [
            $title,
            $address->getStreet()->toString(),
            $address->getPostalCode()->toString(),
            $address->getLocality()->toString(),
            $address->getCountryCode()->toString(),
        ];
        if ($this->duplicatePlacesPerUser) {
            $parts[] = $currentUserId;
        }
        return $parts;
    }

    private function escapeSpecialCharacters(string $query): string
    {
        return (new AsciiSlugger())->slug($query, '_')->toString();
    }
}
