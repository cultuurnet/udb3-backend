<?php

namespace CultuurNet\UDB3\Label\Events;

use CultuurNet\UDB3\Label\ValueObjects\LabelName;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;

class CreatedTest extends TestCase
{
    /**
     * @var UUID
     */
    protected $uuid;

    /**
     * @var LabelName
     */
    protected $name;

    /**
     * @var Visibility
     */
    protected $visibility;

    /**
     * @var Privacy
     */
    protected $privacy;

    /**
     * @var Created
     */
    protected $created;

    protected function setUp()
    {
        $this->uuid = new UUID();

        $this->name = new LabelName('labelName');

        $this->visibility = Visibility::INVISIBLE();

        $this->privacy = Privacy::PRIVACY_PRIVATE();

        $this->created = new Created(
            $this->uuid,
            $this->name,
            $this->visibility,
            $this->privacy
        );
    }

    /**
     * @test
     */
    public function it_extends_an_event()
    {
        $this->assertTrue(is_subclass_of(
            $this->created,
            AbstractEvent::class
        ));
    }

    /**
     * @test
     */
    public function it_stores_a_uuid()
    {
        $this->assertEquals($this->uuid, $this->created->getUuid());
    }

    /**
     * @test
     */
    public function it_stores_a_name()
    {
        $this->assertEquals($this->name, $this->created->getName());
    }

    /**
     * @test
     */
    public function it_stores_a_visibility()
    {
        $this->assertEquals($this->visibility, $this->created->getVisibility());
    }

    /**
     * @test
     */
    public function it_stores_a_privacy()
    {
        $this->assertEquals($this->privacy, $this->created->getPrivacy());
    }

    /**
     * @test
     */
    public function it_can_deserialize()
    {
        $created = Created::deserialize($this->createdAsArray());

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
        return [
            Created::UUID => $this->created->getUuid()->toNative(),
            Created::NAME => $this->created->getName()->toNative(),
            Created::VISIBILITY => $this->created->getVisibility()->toNative(),
            Created::PRIVACY => $this->created->getPrivacy()->toNative(),
        ];
    }
}
