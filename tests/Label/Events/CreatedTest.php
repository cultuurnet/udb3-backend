<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\Events;

use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use PHPUnit\Framework\TestCase;

class CreatedTest extends TestCase
{
    protected UUID $uuid;

    protected string $name;

    protected Visibility $visibility;

    protected Privacy $privacy;

    protected Created $created;

    protected function setUp(): void
    {
        $this->uuid = new UUID('41bf85c1-b9b3-4f21-b7b3-e8276de506a4');

        $this->name = 'labelName';

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
    public function it_extends_an_event(): void
    {
        $this->assertTrue(is_subclass_of(
            $this->created,
            AbstractEvent::class
        ));
    }

    /**
     * @test
     */
    public function it_stores_a_uuid(): void
    {
        $this->assertEquals($this->uuid, $this->created->getUuid());
    }

    /**
     * @test
     */
    public function it_stores_a_name(): void
    {
        $this->assertEquals($this->name, $this->created->getName());
    }

    /**
     * @test
     */
    public function it_stores_a_visibility(): void
    {
        $this->assertEquals($this->visibility, $this->created->getVisibility());
    }

    /**
     * @test
     */
    public function it_stores_a_privacy(): void
    {
        $this->assertEquals($this->privacy, $this->created->getPrivacy());
    }

    /**
     * @test
     */
    public function it_can_deserialize(): void
    {
        $created = Created::deserialize($this->createdAsArray());

        $this->assertEquals($this->created, $created);
    }

    /**
     * @test
     */
    public function it_can_serialize(): void
    {
        $createdAsArray = $this->created->serialize();

        $this->assertEquals($this->createdAsArray(), $createdAsArray);
    }

    protected function createdAsArray(): array
    {
        return [
            Created::UUID => $this->created->getUuid()->toString(),
            Created::NAME => $this->created->getName(),
            Created::VISIBILITY => $this->created->getVisibility()->toString(),
            Created::PRIVACY => $this->created->getPrivacy()->toString(),
        ];
    }
}
