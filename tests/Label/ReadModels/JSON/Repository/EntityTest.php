<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\JSON\Repository;

use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use PHPUnit\Framework\TestCase;

class EntityTest extends TestCase
{
    private Uuid $uuid;

    private string $name;

    private Visibility $visibility;

    private Privacy $privacy;

    private bool $excluded;

    private Entity $entity;

    private Entity $entityWithDefaults;

    protected function setUp(): void
    {
        $this->uuid = new Uuid('17d17095-a628-4cfe-98c2-3306bb6af450');

        $this->name = 'labelName';

        $this->visibility = Visibility::INVISIBLE();

        $this->privacy = Privacy::PRIVACY_PRIVATE();

        $this->excluded = true;

        $this->entity = new Entity(
            $this->uuid,
            $this->name,
            $this->visibility,
            $this->privacy,
            $this->excluded
        );

        $this->entityWithDefaults = new Entity(
            $this->uuid,
            $this->name,
            $this->visibility,
            $this->privacy
        );
    }

    /**
     * @test
     */
    public function it_stores_a_uuid(): void
    {
        $this->assertEquals($this->uuid, $this->entity->getUuid());
    }

    /**
     * @test
     */
    public function it_stores_a_name(): void
    {
        $this->assertEquals($this->name, $this->entity->getName());
    }

    /**
     * @test
     */
    public function it_stores_a_visibility(): void
    {
        $this->assertEquals($this->visibility, $this->entity->getVisibility());
    }

    /**
     * @test
     */
    public function it_stores_a_privacy(): void
    {
        $this->assertEquals($this->privacy, $this->entity->getPrivacy());
    }

    /**
     * @test
     */
    public function it_stores_excluded(): void
    {
        $this->assertEquals($this->excluded, $this->entity->isExcluded());
    }

    /**
     * @test
     */
    public function it_has_a_default_excluded(): void
    {
        $this->assertFalse($this->entityWithDefaults->isExcluded());
    }

    /**
     * @test
     */
    public function it_can_encode_to_json(): void
    {
        $json = Json::encode($this->entity);

        $expectedJson = '{"uuid":"' . $this->uuid->toString()
            . '","name":"' . $this->name
            . '","visibility":"' . $this->visibility->toString()
            . '","privacy":"' . $this->privacy->toString()
            . '","excluded":true}';


        $this->assertEquals($expectedJson, $json);
    }
}
