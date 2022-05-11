<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Commands;

use CultuurNet\UDB3\Role\ValueObjects\Permission;
use PHPUnit\Framework\TestCase;

final class ChangeOwnerTest extends TestCase
{
    private ChangeOwner $changeOwner;

    protected function setUp(): void
    {
        $this->changeOwner = new ChangeOwner(
            'ab56df51-7b9d-468b-8aaa-a275a31098f7',
            '5314f3fd-69fd-4650-8c87-0e7b0b5c0dd3'
        );
    }

    /**
     * @test
     */
    public function it_stores_an_item_id(): void
    {
        $this->assertEquals(
            'ab56df51-7b9d-468b-8aaa-a275a31098f7',
            $this->changeOwner->getItemId()
        );
    }

    /**
     * @test
     */
    public function it_stores_an_new_owner_id(): void
    {
        $this->assertEquals(
            '5314f3fd-69fd-4650-8c87-0e7b0b5c0dd3',
            $this->changeOwner->getNewOwnerId()
        );
    }

    /**
     * @test
     */
    public function it_stores_an_permission(): void
    {
        $this->assertEquals(
            Permission::organisatiesBewerken(),
            $this->changeOwner->getPermission()
        );
    }
}
