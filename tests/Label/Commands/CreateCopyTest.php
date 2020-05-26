<?php

namespace CultuurNet\UDB3\Label\Commands;

use ValueObjects\Identity\UUID;

class CreateCopyTest extends CreateTest
{
    /**
     * @var UUID
     */
    private $parentUuid;

    /**
     * @var CreateCopy
     */
    protected $create;

    protected function setUp()
    {
        parent::setUp();

        $this->parentUuid = new UUID();

        $this->create = new CreateCopy(
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
        $this->assertEquals($this->parentUuid, $this->create->getParentUuid());
    }

    /**
     * @test
     */
    public function it_can_serialize()
    {
        $actualCreate = unserialize(serialize($this->create));

        $this->assertEquals($this->create, $actualCreate);
    }
}
