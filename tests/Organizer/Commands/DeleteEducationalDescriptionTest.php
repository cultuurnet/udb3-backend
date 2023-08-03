<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Commands;

use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use PHPUnit\Framework\TestCase;

final class DeleteEducationalDescriptionTest extends TestCase
{
    private DeleteEducationalDescription $deleteEducationalDescription;

    protected function setUp(): void
    {
        $this->deleteEducationalDescription = new DeleteEducationalDescription(
            'f6549ff4-aafc-436e-8630-48cd05a01943',
            new Language('en')
        );
    }

    /**
     * @test
     */
    public function it_stores_an_organizer_id(): void
    {
        $this->assertEquals('f6549ff4-aafc-436e-8630-48cd05a01943', $this->deleteEducationalDescription->getItemId());
    }

    /**
     * @test
     */
    public function it_stores_a_language(): void
    {
        $this->assertEquals(new Language('en'), $this->deleteEducationalDescription->getLanguage());
    }

    /**
     * @test
     */
    public function it_stores_a_permission(): void
    {
        $this->assertEquals(Permission::organisatiesBewerken(), $this->deleteEducationalDescription->getPermission());
    }
}
