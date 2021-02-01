<?php

namespace CultuurNet\UDB3\Label\Events;

use ValueObjects\Identity\UUID;

class CopyCreatedTest extends CreatedTest
{
    /**
     * @var UUID
     */
    private $parentUuid;

    /**
     * @var CopyCreated
     */
    protected $created;

    public function setUp()
    {
        parent::setUp();

        $this->parentUuid = new UUID();

        $this->created = new CopyCreated(
            $this->uuid,
            $this->name,
            $this->visibility,
            $this->privacy,
            $this->parentUuid
        );
    }

    /**
     * @test
     */
    public function it_stores_a_parent_uuid()
    {
        $this->assertEquals($this->parentUuid, $this->created->getParentUuid());
    }

    /**
     * @test
     */
    public function it_can_deserialize()
    {
        $created = CopyCreated::deserialize($this->createdAsArray());

        $this->assertEquals($this->created, $created);
    }

    /**
     * @test
     */
    public function it_can_serialize()
    {
        $createdAsArray = $this->created->serialize();

        $this->assertEquals($this->createdAsArray(), $createdAsArray);
    }

    /**
     * @return array
     */
    protected function createdAsArray()
    {
        $createdAsArray = parent::createdAsArray();

        $createdAsArray[CopyCreated::PARENT_UUID] = $this->parentUuid;

        return $createdAsArray;
    }
}
