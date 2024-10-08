<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Events\Moderation;

use CultuurNet\UDB3\Offer\Events\AbstractEvent;
use DateTimeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AbstractPublishedTest extends TestCase
{
    private string $itemId;

    private \DateTimeInterface $publicationDate;

    /**
     * @var AbstractPublished&MockObject
     */
    private $abstractPublished;

    protected function setUp(): void
    {
        $this->itemId = '3dc2b894-9a80-11e6-9f33-a24fc0d9649c';

        $this->publicationDate = new \DateTime();

        $this->abstractPublished = $this->getMockForAbstractClass(
            AbstractPublished::class,
            [$this->itemId, $this->publicationDate]
        );
    }

    /**
     * @test
     */
    public function it_derives_from_abstract_event(): void
    {
        $this->assertTrue(is_subclass_of(
            $this->abstractPublished,
            AbstractEvent::class
        ));
    }

    /**
     * @test
     */
    public function it_stores_an_item_id(): void
    {
        $this->assertEquals(
            $this->itemId,
            $this->abstractPublished->getItemId()
        );
    }

    /**
     * @test
     */
    public function it_stores_an_publication_date(): void
    {
        $this->assertEquals(
            $this->publicationDate,
            $this->abstractPublished->getPublicationDate()
        );
    }

    /**
     * @test
     */
    public function it_can_serialize(): void
    {
        $expectedArray = [
            'item_id' => $this->itemId,
            'publication_date' => $this->publicationDate->format(DateTimeInterface::ATOM),
        ];

        $actualArray = $this->abstractPublished->serialize();

        $this->assertEquals($expectedArray, $actualArray);
    }
}
