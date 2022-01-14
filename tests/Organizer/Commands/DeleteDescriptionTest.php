<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Commands;

use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use PHPUnit\Framework\TestCase;

final class DeleteDescriptionTest extends TestCase
{
    private DeleteDescription $deleteDescription;

    protected function setUp(): void
    {
        $this->deleteDescription = new DeleteDescription(
            'f6549ff4-aafc-436e-8630-48cd05a01943',
            new Language('en')
        );
    }

    /**
     * @test
     */
    public function it_stores_an_organizer_id(): void
    {
        $this->assertEquals('f6549ff4-aafc-436e-8630-48cd05a01943', $this->deleteDescription->getItemId());
    }

    /**
     * @test
     */
    public function it_stores_a_language(): void
    {
        $this->assertEquals(new Language('en'), $this->deleteDescription->getLanguage());
    }

    /**
     * @test
     */
    public function it_stores_a_permission(): void
    {
        $this->assertEquals(Permission::ORGANISATIES_BEWERKEN(), $this->deleteDescription->getPermission());
    }
}
