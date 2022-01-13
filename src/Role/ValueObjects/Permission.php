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

    public static function getAllowedPermissions(): array
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

    public static function getByName(string $key): Permission
    {
        return new self(ucfirst(str_replace('_', ' ', strtolower($key))));
    }
}
