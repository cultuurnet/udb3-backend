<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\Commands;

use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use PHPUnit\Framework\TestCase;

class CreateTest extends TestCase
{
    protected Uuid $uuid;

    protected LabelName $name;

    protected Visibility $visibility;

    protected Privacy $privacy;

    private Create $create;

    protected function setUp(): void
    {
        $this->uuid = new Uuid('fec0c4d7-80e1-4713-98f9-4c436af6e650');

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
    public function it_extends_an_abstract_command(): void
    {
        $this->assertTrue(is_subclass_of(
            $this->create,
            AbstractCommand::class
        ));
    }

    /**
     * @test
     */
    public function it_stores_a_uuid(): void
    {
        $this->assertEquals($this->uuid, $this->create->getUuid());
    }

    /**
     * @test
     */
    public function it_stores_a_name(): void
    {
        $this->assertEquals($this->name, $this->create->getName());
    }

    /**
     * @test
     */
    public function it_stores_a_visibility(): void
    {
        $this->assertEquals($this->visibility, $this->create->getVisibility());
    }

    /**
     * @test
     */
    public function it_stores_a_privacy(): void
    {
        $this->assertEquals($this->privacy, $this->create->getPrivacy());
    }

    /**
     * @test
     */
    public function it_can_serialize(): void
    {
        $actualCreate = unserialize(serialize($this->create));

        $this->assertEquals($this->create, $actualCreate);
    }
}
