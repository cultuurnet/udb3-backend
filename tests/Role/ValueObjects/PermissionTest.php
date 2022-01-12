<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\ValueObjects;

use PHPUnit\Framework\TestCase;

class PermissionTest extends TestCase
{
    /**
     * @test
     */
    public function it_has_a_fixed_list_of_possible_permissions()
    {
        $permissions = Permission::getAllowedValues();

        $this->assertEquals(
            [
                Permission::aanbodBewerken()->toString(),
                Permission::aanbodModereren()->toString(),
                Permission::aanbodVerwijderen()->toString(),
                Permission::organisatiesBeheren()->toString(),
                Permission::organisatiesBewerken()->toString(),
                Permission::gebruikersBeheren()->toString(),
                Permission::labelsBeheren()->toString(),
                Permission::voorzieningenBewerken()->toString(),
                Permission::productiesAanmaken()->toString(),
                Permission::filmsAanmaken()->toString(),
            ],
            $permissions
        );
    }
}
