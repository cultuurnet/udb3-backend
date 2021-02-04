<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Curators\Events;

use CultuurNet\UDB3\Deserializer\DataValidationException;
use CultuurNet\UDB3\Deserializer\MissingValueException;
use CultuurNet\UDB3\Curators\PublisherName;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;

final class NewsArticleAboutEventAddedJSONDeserializerTest extends TestCase
{
    /**
     * @var NewsArticleAboutEventAddedJSONDeserializer
     */
    private $deserializer;

    protected function setUp()
    {
        parent::setUp();
        $this->deserializer = new NewsArticleAboutEventAddedJSONDeserializer();
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_news_article_id_is_missing()
    {
        $event = json_encode(
            [
                'eventId' => 'C0D870F6-2883-4565-A020-7CF12BDE5F51',
                'publisher' => 'bruzz',
            ]
        );

        $this->expectException(MissingValueException::class);
        $this->deserializer->deserialize(new StringLiteral($event));
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_event_id_is_missing()
    {
        $event = json_encode(
            [
                'newsArticleId' => 'c4c19563-06e3-43fa-a15c-73a91c54b27e',
                'publisher' => 'bruzz',
            ]
        );

        $this->expectException(MissingValueException::class);
        $this->deserializer->deserialize(new StringLiteral($event));
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_publisher_is_missing()
    {
        $event = json_encode(
            [
                'eventId' => 'C0D870F6-2883-4565-A020-7CF12BDE5F51',
                'newsArticleId' => 'c4c19563-06e3-43fa-a15c-73a91c54b27e',
            ]
        );

        $this->expectException(MissingValueException::class);
        $this->deserializer->deserialize(new StringLiteral($event));
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_publisher_is_invalid()
    {
        $event = json_encode(
            [
                'eventId' => 'C0D870F6-2883-4565-A020-7CF12BDE5F51',
                'newsArticleId' => 'c4c19563-06e3-43fa-a15c-73a91c54b27e',
                'publisher' => '',
            ]
        );

        $this->expectException(DataValidationException::class);
        $this->deserializer->deserialize(new StringLiteral($event));
    }

    /**
     * @test
     */
    public function it_should_deserialize_a_complete_event()
    {
        $expected = new NewsArticleAboutEventAdded(
            'c4c19563-06e3-43fa-a15c-73a91c54b27e',
            'C0D870F6-2883-4565-A020-7CF12BDE5F51',
            new PublisherName('bruzz')
        );

        $event = json_encode(
            [
                'newsArticleId' => 'c4c19563-06e3-43fa-a15c-73a91c54b27e',
                'eventId' => 'C0D870F6-2883-4565-A020-7CF12BDE5F51',
                'publisher' => 'bruzz',
            ]
        );
        $actual = $this->deserializer->deserialize(new StringLiteral($event));

        $this->assertEquals($expected, $actual);
    }
}
