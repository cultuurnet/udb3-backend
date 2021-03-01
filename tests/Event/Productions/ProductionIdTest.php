<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Productions;

use PHPUnit\Framework\TestCase;

class ProductionIdTest extends TestCase
{
    /**
     * @test
     */
    public function it_generates_unique_identifiers(): void
    {
        $id1 = ProductionId::generate();
        $id2 = ProductionId::generate();

        $this->assertFalse($id1->equals($id2));
    }

    /**
     * @test
     */
    public function it_can_assert_equality(): void
    {
        $id1 = ProductionId::generate();
        $id2 = ProductionId::fromNative($id1->toNative());

        $this->assertTrue($id1->equals($id2));
    }
}
