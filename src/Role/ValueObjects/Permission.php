<?php

namespace CultuurNet\UDB3\Role\ValueObjects;

use ValueObjects\Enum\Enum;

/**
 * Class Permission
 * @package CultuurNet\UDB3\Role\ValueObjects
 * @method static Permission AANBOD_BEWERKEN()
 * @method static Permission AANBOD_MODEREREN()
 * @method static Permission AANBOD_VERWIJDEREN()
 * @method static Permission ORGANISATIES_BEHEREN()
 * @method static Permission ORGANISATIES_BEWERKEN()
 * @method static Permission GEBRUIKERS_BEHEREN()
 * @method static Permission LABELS_BEHEREN()
 * @method static Permission MEDIA_UPLOADEN()
 * @method static Permission VOORZIENINGEN_BEWERKEN()
 * @method static Permission PRODUCTIES_AANMAKEN()
 */
class Permission extends Enum
{
    public const AANBOD_BEWERKEN = 'Aanbod bewerken';
    public const AANBOD_MODEREREN = 'Aanbod modereren';
    public const AANBOD_VERWIJDEREN = 'Aanbod verwijderen';
    public const ORGANISATIES_BEHEREN = 'Organisaties beheren';
    public const ORGANISATIES_BEWERKEN = 'Organisaties bewerken';
    public const GEBRUIKERS_BEHEREN = 'Gebruikers beheren';
    public const LABELS_BEHEREN = 'Labels beheren';
    public const MEDIA_UPLOADEN = 'Media uploaden';
    public const VOORZIENINGEN_BEWERKEN = 'Voorzieningen bewerken';
    public const PRODUCTIES_AANMAKEN = 'Producties aanmaken';
}
