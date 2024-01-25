<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\ReadModel\Duplicate;

use CultuurNet\UDB3\Model\ValueObject\Geography\Address;

class UniqueAddressIdentifierFactory
{
    public function create(string $title, Address $address, string $currentUserId): string
    {
        $parts = array_map(
            fn ($part) => str_replace(' ', '_', trim($part)),
            $this->getParts($title, $address, $currentUserId)
        );

        return mb_strtolower(implode('_', array_filter($parts)));
    }

    private function getParts(string $title, Address $address, string $currentUserId): array
    {
        return [
            $title,
            $address->getStreet()->toString(),
            $address->getPostalCode()->toString(),
            $address->getLocality()->toString(),
            $address->getCountryCode()->toString(),
            $currentUserId,
        ];
    }
}
