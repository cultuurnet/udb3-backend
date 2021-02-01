<?php

namespace CultuurNet\UDB3\Role\ValueObjects;

use PHPUnit\Framework\TestCase;

class PermissionTest extends TestCase
{
    /**
     * @test
     */
    public function it_has_a_fixed_list_of_possible_permissions()
    {
        $permissions = Permission::getConstants();

        $this->assertEquals(
            [
                Permission::AANBOD_BEWERKEN()->getName() => Permission::AANBOD_BEWERKEN,
                Permission::AANBOD_MODEREREN()->getName() => Permission::AANBOD_MODEREREN,
                Permission::AANBOD_VERWIJDEREN()->getName() => Permission::AANBOD_VERWIJDEREN,
                Permission::GEBRUIKERS_BEHEREN()->getName() => Permission::GEBRUIKERS_BEHEREN,
                Permission::LABELS_BEHEREN()->getName() => Permission::LABELS_BEHEREN,
                Permission::ORGANISATIES_BEHEREN()->getName() => Permission::ORGANISATIES_BEHEREN,
                Permission::MEDIA_UPLOADEN()->getName() => Permission::MEDIA_UPLOADEN,
                Permission::VOORZIENINGEN_BEWERKEN()->getName() => Permission::VOORZIENINGEN_BEWERKEN,
                Permission::ORGANISATIES_BEWERKEN()->getName() => Permission::ORGANISATIES_BEWERKEN,
                Permission::PRODUCTIES_AANMAKEN()->getName() => Permission::PRODUCTIES_AANMAKEN,
            ],
            $permissions
        );
    }
}
