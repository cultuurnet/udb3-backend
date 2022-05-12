<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Events;

use PHPUnit\Framework\TestCase;

final class OwnerChangedTest extends TestCase
{
    private OwnerChanged $ownerChanged;

    protected function setUp(): void
    {
        $this->ownerChanged = new OwnerChanged(
            'ab56df51-7b9d-468b-8aaa-a275a31098f7',
            '5314f3fd-69fd-4650-8c87-0e7b0b5c0dd3'
        );
    }

    /**
     * @test
     */
    public function it_stores_an_organizer_id(): void
    {
        $this->assertEquals(
            'ab56df51-7b9d-468b-8aaa-a275a31098f7',
            $this->ownerChanged->getOrganizerId()
        );
    }

    /**
     * @test
     */
    public function it_stores_an_owner_id(): void
    {
        $this->assertEquals(
            '5314f3fd-69fd-4650-8c87-0e7b0b5c0dd3',
            $this->ownerChanged->getNewOwnerId()
        );
    }

    /**
     * @test
     */
    public function it_can_serialize(): void
    {
        $this->assertEquals(
            [
                'organizer_id' => 'ab56df51-7b9d-468b-8aaa-a275a31098f7',
                'new_owner_id' => '5314f3fd-69fd-4650-8c87-0e7b0b5c0dd3',
            ],
            $this->ownerChanged->serialize()
        );
    }

    /**
     * @test
     */
    public function it_can_deserialize(): void
    {
        $this->assertEquals(
            $this->ownerChanged,
            OwnerChanged::deserialize(
                [
                    'organizer_id' => 'ab56df51-7b9d-468b-8aaa-a275a31098f7',
                    'new_owner_id' => '5314f3fd-69fd-4650-8c87-0e7b0b5c0dd3',
                ]
            )
        );
    }
}
