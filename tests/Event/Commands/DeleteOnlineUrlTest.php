<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Commands;

use CultuurNet\UDB3\Role\ValueObjects\Permission;
use PHPUnit\Framework\TestCase;

final class DeleteOnlineUrlTest extends TestCase
{
    private DeleteOnlineUrl $deleteOnlineUrl;

    protected function setUp(): void
    {
        $this->deleteOnlineUrl = new DeleteOnlineUrl('8ca71433-7a38-4c46-bc01-b3388da89214');
    }

    /**
     * @test
     */
    public function it_stores_an_eventId(): void
    {
        $this->assertEquals('8ca71433-7a38-4c46-bc01-b3388da89214', $this->deleteOnlineUrl->getItemId());
    }

    /**
     * @test
     */
    public function it_stores_permission(): void
    {
        $this->assertEquals(Permission::aanbodBewerken(), $this->deleteOnlineUrl->getPermission());
    }
}
