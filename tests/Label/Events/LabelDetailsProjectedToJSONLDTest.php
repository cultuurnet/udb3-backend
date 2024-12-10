<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\Events;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use PHPUnit\Framework\TestCase;

class LabelDetailsProjectedToJSONLDTest extends TestCase
{
    private Uuid $uuid;

    private LabelDetailsProjectedToJSONLD $labelDetailsProjectedToJSONLD;

    protected function setUp(): void
    {
        $this->uuid = new Uuid('3960ff99-ceab-4b44-aa51-dc7a187b77e0');

        $this->labelDetailsProjectedToJSONLD = new LabelDetailsProjectedToJSONLD(
            $this->uuid
        );
    }

    /**
     * @test
     */
    public function it_stores_a_uuid(): void
    {
        $this->assertEquals(
            $this->uuid,
            $this->labelDetailsProjectedToJSONLD->getUuid()
        );
    }

    /**
     * @test
     */
    public function it_can_deserialize(): void
    {
        $asArray = ['uuid' => $this->uuid->toString()];

        $this->assertEquals(
            $this->labelDetailsProjectedToJSONLD,
            LabelDetailsProjectedToJSONLD::deserialize($asArray)
        );
    }

    /**
     * @test
     */
    public function it_can_serialize(): void
    {
        $expectedArray = ['uuid' => $this->uuid->toString()];

        $this->assertEquals(
            $expectedArray,
            $this->labelDetailsProjectedToJSONLD->serialize()
        );
    }
}
