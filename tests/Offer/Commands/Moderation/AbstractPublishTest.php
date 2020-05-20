<?php

namespace CultuurNet\UDB3\Offer\Commands\Moderation;

use DateTime;
use DateTimeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;

class AbstractPublishTest extends TestCase
{
    /**
     * @var UUID
     */
    private $uuid;

    /**
     * @var \DateTimeInterface
     */
    private $publicationDate;

    /**
     * @var AbstractPublish|MockObject
     */
    private $abstractPublish;

    public function setUp()
    {
        $this->uuid = new UUID();

        $this->publicationDate = new DateTime();

        $this->abstractPublish = $this->getMockForAbstractClass(
            AbstractPublish::class,
            [$this->uuid->toNative(), $this->publicationDate]
        );
    }

    /**
     * @test
     */
    public function it_is_an_abstract_moderation_command()
    {
        $this->assertTrue(is_subclass_of(
            $this->abstractPublish,
            AbstractModerationCommand::class
        ));
    }

    /**
     * @test
     */
    public function it_can_store_an_publication_date()
    {
        $this->assertEquals(
            $this->publicationDate,
            $this->abstractPublish->getPublicationDate()
        );
    }

    /**
     * @test
     */
    public function it_has_a_default_publication_date_of_now()
    {
        $before = new DateTime();

        /** @var AbstractPublish $abstractPublish */
        $abstractPublish = $this->getMockForAbstractClass(
            AbstractPublish::class,
            [$this->uuid->toNative()]
        );

        $after = new DateTime();

        $publicationDate = $abstractPublish->getPublicationDate();

        $this->assertInstanceOf(DateTimeInterface::class, $publicationDate);
        $this->assertGreaterThanOrEqual($before, $publicationDate);
        $this->assertLessThanOrEqual($after, $publicationDate);
    }
}
