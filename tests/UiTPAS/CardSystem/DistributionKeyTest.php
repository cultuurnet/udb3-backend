<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTPAS\CardSystem;

use CultuurNet\UDB3\UiTPAS\ValueObject\Id;
use PHPUnit\Framework\TestCase;

class DistributionKeyTest extends TestCase
{
    /**
     * @test
     */
    public function it_exposes_its_id_and_name(): void
    {
        $distributionKey = new DistributionKey(new Id('123'), '3 euro per dag');

        $this->assertEquals('123', $distributionKey->getId()->toNative());
        $this->assertEquals('3 euro per dag', $distributionKey->getName());
    }

    /**
     * @test
     */
    public function it_allows_a_missing_name(): void
    {
        $distributionKey = new DistributionKey(new Id('456'));

        $this->assertEquals('456', $distributionKey->getId()->toNative());
        $this->assertNull($distributionKey->getName());
    }
}
