<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\ValueObjects;

use CultuurNet\UDB3\Model\ValueObject\String\Enum;

class Permission extends Enum
{
    public static function getAllowedValues(): array
    {
        return [
            'Aanbod bewerken',
            'Aanbod modereren',
            'Aanbod verwijderen',
            'Organisaties beheren',
            'Organisaties bewerken',
            'Gebruikers beheren',
            'Labels beheren',
            'Voorzieningen bewerken',
            'Producties aanmaken',
            'Films aanmaken',
        ];
    }

    public static function getAllPermissions(): array
    {
        return array_map(
            fn (string $permission) => new Permission($permission),
            self::getAllowedValues()
        );
    }

    public static function aanbodBewerken(): Permission
    {
        return new self('Aanbod bewerken');
    }

    public static function aanbodModereren(): Permission
    {
        return new self('Aanbod modereren');
    }

    public static function aanbodVerwijderen(): Permission
    {
        return new self('Aanbod verwijderen');
    }

    public static function organisatiesBeheren(): Permission
    {
        return new self('Organisaties beheren');
    }

    public static function organisatiesBewerken(): Permission
    {
        return new self('Organisaties bewerken');
    }

    public static function gebruikersBeheren(): Permission
    {
        return new self('Gebruikers beheren');
    }

    public static function labelsBeheren(): Permission
    {
        return new self('Labels beheren');
    }

    public static function voorzieningenBewerken(): Permission
    {
        return new self('Voorzieningen bewerken');
    }

    public static function productiesAanmaken(): Permission
    {
        return new self('Producties aanmaken');
    }

    public static function filmsAanmaken(): Permission
    {
        return new self('Films aanmaken');
    }

    // The API exposes the permissions in uppercase and underscore format.
    // The business layer and database use the lowercase and space format.
    // This method should only be used inside the context of the API.
    public static function fromUpperCaseString(string $upperCaseValue): Permission
    {
        return new Permission(ucfirst(str_replace('_', ' ', strtolower($upperCaseValue))));
    }

    // The API exposes the permissions in uppercase and underscore format.
    // The business layer and database use the lowercase and space format.
    // This method should only be used inside the context of the API.
    public function toUpperCaseString(): string
    {
        return str_replace(' ', '_', strtoupper($this->toString()));
    }
}
