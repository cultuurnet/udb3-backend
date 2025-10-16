<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\ReadModel\Duplicate;

use CultuurNet\UDB3\Model\ValueObject\Geography\Address;
use Symfony\Component\String\Slugger\AsciiSlugger;

final class UniqueAddressIdentifierFactory
{
    public function create(string $title, Address $address): string
    {
        $parts = array_map(
            fn ($part) => str_replace(' ', '_', trim($part)),
            $this->getParts($title, $address)
        );

        return mb_strtolower($this->escapeSpecialCharacters(implode('_', array_filter($parts))));
    }

    public function createForUser(string $title, Address $address, string $currentUserId): string
    {
        $parts = array_map(
            fn ($part) => str_replace(' ', '_', trim($part)),
            $this->getParts($title, $address, $currentUserId)
        );

        return mb_strtolower($this->escapeReservedElasticsearchCharacters(implode('_', array_filter($parts))));
    }

    private function getParts(string $title, Address $address, string $currentUserId = null): array
    {
        $parts = [
            $title,
            $address->getStreet()->toString(),
            $address->getPostalCode()->toString(),
            $address->getLocality()->toString(),
            $address->getCountryCode()->toString(),
        ];

        if ($currentUserId !== null) {
            $parts[] = $currentUserId;
        }

        return $parts;
    }

    private function escapeReservedElasticsearchCharacters(string $query): string
    {
        // List of special characters that need escaping
        $specialChars = ['\\', '!', '(', ')', '{', '}', '[', ']', '^', '"', '~', '*', '?', ':', '/'];

        // Escape each character
        foreach ($specialChars as $char) {
            $query = str_replace($char, '\\' . $char, $query);
        }

        return $query;
    }

    private function escapeSpecialCharacters(string $query): string
    {
        return (new AsciiSlugger())->slug($query, '_')->toString();
    }
}
