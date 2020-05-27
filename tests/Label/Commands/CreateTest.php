<?php

namespace CultuurNet\UDB3\Label\Commands;

use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Label\ValueObjects\LabelName;
use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;

class CreateTest extends TestCase
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
     * @var Create
     */
    protected $create;

    protected function setUp()
    {
        $this->uuid = new UUID();

        $this->name = new LabelName('labelName');

        $this->visibility = Visibility::VISIBLE();

        $this->privacy = Privacy::PRIVACY_PUBLIC();

        $this->create = new Create(
            $this->uuid,
            $this->name,
            $this->visibility,
            $this->privacy
        );
    }

    /**
     * @test
     */
    public function it_extends_an_abstract_command()
    {
        $this->assertTrue(is_subclass_of(
            $this->create,
            AbstractCommand::class
        ));
    }

    /**
     * @test
     */
    public function it_stores_a_uuid()
    {
        $this->assertEquals($this->uuid, $this->create->getUuid());
    }

    /**
     * @test
     */
    public function it_stores_a_name()
    {
        $this->assertEquals($this->name, $this->create->getName());
    }

    /**
     * @test
     */
    public function it_stores_a_visibility()
    {
        $this->assertEquals($this->visibility, $this->create->getVisibility());
    }

    /**
     * @test
     */
    public function it_stores_a_privacy()
    {
        $this->assertEquals($this->privacy, $this->create->getPrivacy());
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
