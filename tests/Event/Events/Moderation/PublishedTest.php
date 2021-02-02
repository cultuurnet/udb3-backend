<?php

namespace CultuurNet\UDB3\Event\Events\Moderation;

use PHPUnit\Framework\TestCase;

class PublishedTest extends TestCase
{
    /**
     * @var string
     */
    private $itemId;

    /**
     * @var \DateTimeInterface
     */
    private $publicationDate;

    /**
     * @var Published
     */
    private $published;

    protected function setUp()
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
    public function it_can_deserialize()
    {
        $publishedAsArray = [
            'item_id' => $this->itemId,
            'publication_date' => $this->publicationDate->format(\DateTime::ATOM),
        ];

        $this->assertEquals(
            $this->published,
            Published::deserialize($publishedAsArray)
        );
    }
}
