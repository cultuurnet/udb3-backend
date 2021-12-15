<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Commands;

use CultuurNet\UDB3\Model\ValueObject\Text\Description;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use PHPUnit\Framework\TestCase;

final class UpdateDescriptionTest extends TestCase
{
    private UpdateDescription $updateDescription;

    protected function setUp(): void
    {
        $this->updateDescription = new UpdateDescription(
            '914dfde8-940a-4b8f-8316-029b1a0248aa',
            new Description('This is the description of the organizer'),
            new Language('en')
        );
    }

    /**
     * @test
     */
    public function it_stores_an_organizer_id(): void
    {
        $this->assertEquals(
            '914dfde8-940a-4b8f-8316-029b1a0248aa',
            $this->updateDescription->getItemId()
        );
    }

    /**
     * @test
     */
    public function it_stores_a_description(): void
    {
        $this->assertEquals(
            new Description('This is the description of the organizer'),
            $this->updateDescription->getDescription()
        );
    }

    /**
     * @test
     */
    public function it_stores_a_language(): void
    {
        $this->assertEquals(
            new Language('en'),
            $this->updateDescription->getLanguage()
        );
    }

    /**
     * @test
     */
    public function it_stores_a_permission(): void
    {
        $this->assertEquals(
            Permission::ORGANISATIES_BEWERKEN(),
            $this->updateDescription->getPermission()
        );
    }
}
