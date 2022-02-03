<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\Events;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use PHPUnit\Framework\TestCase;

class LabelDetailsProjectedToJSONLDTest extends TestCase
{
    /**
     * @var UUID
     */
    private $uuid;

    /**
     * @var LabelDetailsProjectedToJSONLD
     */
    private $labelDetailsProjectedToJSONLD;

    protected function setUp()
    {
        $this->uuid = new UUID();

        $this->labelDetailsProjectedToJSONLD = new LabelDetailsProjectedToJSONLD(
            $this->uuid
        );
    }

    /**
     * @test
     */
    public function it_stores_a_uuid()
    {
        $this->assertEquals(
            $this->uuid,
            $this->labelDetailsProjectedToJSONLD->getUuid()
        );
    }

    /**
     * @test
     */
    public function it_can_deserialize()
    {
        $asArray = ['uuid' => $this->uuid->toNative()];

        $this->assertEquals(
            $this->labelDetailsProjectedToJSONLD,
            LabelDetailsProjectedToJSONLD::deserialize($asArray)
        );
    }

    /**
     * @test
     */
    public function it_can_serialize()
    {
        $expectedArray = ['uuid' => $this->uuid->toNative()];

        $this->assertEquals(
            $expectedArray,
            $this->labelDetailsProjectedToJSONLD->serialize()
        );
    }
}
