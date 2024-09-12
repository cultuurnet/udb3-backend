<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events\Moderation;

use DateTimeInterface;
use PHPUnit\Framework\TestCase;

class PublishedTest extends TestCase
{
    private string $itemId;

    private \DateTimeInterface $publicationDate;

    private Published $published;

    protected function setUp(): void
    {
        $this->itemId = '75d90bc2-9b64-11e6-9f33-a24fc0d9649c';

        $this->publicationDate = new \DateTime('2016-11-11T12:13:14');

        $this->published = new Published(
            $this->itemId,
            $this->publicationDate
        );
    }

    /**
     * @test
     */
    public function it_can_deserialize(): void
    {
        $publishedAsArray = [
            'item_id' => $this->itemId,
            'publication_date' => $this->publicationDate->format(DateTimeInterface::ATOM),
        ];

        $this->assertEquals(
            $this->published,
            Published::deserialize($publishedAsArray)
        );
    }
}
