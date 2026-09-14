<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTPAS\CardSystem;

use CultuurNet\UDB3\UiTPAS\ValueObject\Id;
use PHPUnit\Framework\TestCase;

class CardSystemTest extends TestCase
{
    /**
     * @test
     */
    public function it_exposes_its_id_and_name(): void
    {
        $cardSystem = new CardSystem(new Id('1'), 'UiTPAS Dender', true);

        $this->assertEquals('1', $cardSystem->getId()->toNative());
        $this->assertEquals('UiTPAS Dender', $cardSystem->getName());
    }

    /**
     * @test
     */
    public function it_has_no_distribution_keys_by_default(): void
    {
        $cardSystem = new CardSystem(new Id('1'), 'UiTPAS Dender', true);

        $this->assertEquals([], $cardSystem->getDistributionKeys());
    }

    /**
     * @test
     */
    public function it_returns_a_new_instance_with_distribution_keys_without_mutating_the_original(): void
    {
        $original = new CardSystem(new Id('1'), 'UiTPAS Dender', true);

        $distributionKeys = [
            new DistributionKey(new Id('123'), '3 euro per dag', true),
            new DistributionKey(new Id('456'), null, true),
        ];

        $withKeys = $original->withDistributionKeys($distributionKeys);

        $this->assertNotSame($original, $withKeys);
        $this->assertEquals([], $original->getDistributionKeys());
        $this->assertEquals($distributionKeys, $withKeys->getDistributionKeys());
        $this->assertEquals('1', $withKeys->getId()->toNative());
        $this->assertEquals('UiTPAS Dender', $withKeys->getName());
    }
}
