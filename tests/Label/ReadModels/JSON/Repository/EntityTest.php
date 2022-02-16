<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\JSON\Repository;

use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use CultuurNet\UDB3\StringLiteral;

class EntityTest extends TestCase
{
    /**
     * @var UUID
     */
    private $uuid;

    /**
     * @var StringLiteral
     */
    private $name;

    /**
     * @var Visibility
     */
    private $visibilty;

    /**
     * @var Privacy
     */
    private $privacy;

    /**
     * @var UUID
     */
    private $parentUuid;

    private int $count;

    /**
     * @var Entity
     */
    private $entity;

    /**
     * @var Entity
     */
    private $entityWithDefaults;

    protected function setUp()
    {
        $this->uuid = new UUID('17d17095-a628-4cfe-98c2-3306bb6af450');

        $this->name = new StringLiteral('labelName');

        $this->visibilty = Visibility::INVISIBLE();

        $this->privacy = Privacy::PRIVACY_PRIVATE();

        $this->parentUuid = new UUID('17ee8501-0168-4469-ba37-458a6a526466');

        $this->count = 666;

        $this->entity = new Entity(
            $this->uuid,
            $this->name,
            $this->visibilty,
            $this->privacy,
            $this->parentUuid,
            $this->count
        );

        $this->entityWithDefaults = new Entity(
            $this->uuid,
            $this->name,
            $this->visibilty,
            $this->privacy
        );
    }

    /**
     * @test
     */
    public function it_stores_a_uuid()
    {
        $this->assertEquals($this->uuid, $this->entity->getUuid());
    }

    /**
     * @test
     */
    public function it_stores_a_name()
    {
        $this->assertEquals($this->name, $this->entity->getName());
    }

    /**
     * @test
     */
    public function it_stores_a_visibility()
    {
        $this->assertEquals($this->visibilty, $this->entity->getVisibility());
    }

    /**
     * @test
     */
    public function it_stores_a_privacy()
    {
        $this->assertEquals($this->privacy, $this->entity->getPrivacy());
    }

    /**
     * @test
     */
    public function it_stores_a_parent_uuid()
    {
        $this->assertEquals($this->parentUuid, $this->entity->getParentUuid());
    }

    /**
     * @test
     */
    public function it_stores_a_count()
    {
        $this->assertEquals($this->count, $this->entity->getCount());
    }

    /**
     * @test
     */
    public function it_has_a_default_parent_uuid_of_null()
    {
        $this->assertEquals(null, $this->entityWithDefaults->getParentUuid());
    }

    /**
     * @test
     */
    public function it_has_a_Default_count_of_zero()
    {
        $this->assertEquals(
            0,
            $this->entityWithDefaults->getCount()
        );
    }

    /**
     * @test
     */
    public function it_requires_a_positive_count(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Entity(
            $this->uuid,
            $this->name,
            $this->visibilty,
            $this->privacy,
            $this->parentUuid,
            -1
        );
    }

    /**
     * @test
     */
    public function it_can_encode_to_json()
    {
        $json = json_encode($this->entity);

        $expectedJson = '{"uuid":"' . $this->uuid->toString()
            . '","name":"' . $this->name->toNative()
            . '","visibility":"' . $this->visibilty->toNative()
            . '","privacy":"' . $this->privacy->toString() . '"}';

        $this->assertEquals($expectedJson, $json);
    }
}
