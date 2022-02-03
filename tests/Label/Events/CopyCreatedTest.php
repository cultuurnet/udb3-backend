<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\Events;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;

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

        $this->parentUuid = new UUID('5ae6d41e-5321-43e8-989d-a4e77864b397');

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
