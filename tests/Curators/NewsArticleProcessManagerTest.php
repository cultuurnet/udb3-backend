<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Curators;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Deserializer\SimpleDeserializerLocator;
use CultuurNet\UDB3\Broadway\AMQP\EventBusForwardingConsumer;
use CultuurNet\UDB3\Curators\Events\NewsArticleAboutEventAddedJSONDeserializer;
use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\Offer\Commands\AddLabel;
use CultuurNet\UDB3\SimpleEventBus;
use InvalidArgumentException;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;

final class NewsArticleProcessManagerTest extends TestCase
{
    /**
     * @var EventBusForwardingConsumer
     */
    private $eventBusForwardingConsumer;

    /**
     * @var TraceableCommandBus
     */
    private $commandBus;

    /**
     * @var array
     */
    private $messageDeliveryInfo;

    /**
     * @var LabelFactory|MockObject
     */
    private $labelFactory;

    public function setUp()
    {
        parent::setUp();

        $this->commandBus = new TraceableCommandBus();
        $this->labelFactory = $this->createMock(LabelFactory::class);

        $processManager = new NewsArticleProcessManager(
            $this->labelFactory,
            $this->commandBus
        );

        $eventBus = new SimpleEventBus();
        $eventBus->subscribe($processManager);

        $deserializer = new NewsArticleAboutEventAddedJSONDeserializer();
        $deserializerLocator = new SimpleDeserializerLocator();
        $deserializerLocator->registerDeserializer(
            NewsArticleAboutEventAddedJSONDeserializer::getContentType(),
            $deserializer
        );

        /* @var AMQPStreamConnection|MockObject $connection */
        $connection = $this->createMock(AMQPStreamConnection::class);

        /* @var AMQPChannel|MockObject $channel */
        $channel = $this->createMock(AMQPChannel::class);

        $connection->expects($this->any())
            ->method('channel')
            ->willReturn($channel);

        $this->eventBusForwardingConsumer = new EventBusForwardingConsumer(
            $connection,
            $eventBus,
            $deserializerLocator,
            new StringLiteral('test_consumer_tag'),
            new StringLiteral('test_exchange'),
            new StringLiteral('test_queue')
        );

        $this->messageDeliveryInfo = [
            'channel' => $channel,
            'delivery_tag' => 'test_consumer_tag',
        ];
    }

    /**
     * @test
     */
    public function it_should_add_a_matching_label_to_an_event_if_a_news_article_is_created_about_it()
    {
        $message = new AMQPMessage(
            json_encode(
                [
                    'newsArticleId' => 'c4c19563-06e3-43fa-a15c-73a91c54b27e',
                    'eventId' => 'F8E5055F-66C4-4929-ABB9-822B9F5328F1',
                    'publisher' => 'bruzz',
                ]
            ),
            [
                'content_type' => 'application/vnd.cultuurnet.curators-api.events.news-article-about-event-added+json',
            ]
        );
        $message->delivery_info = $this->messageDeliveryInfo;

        $expectedLabel = new Label('TEST_LABEL', false);

        $this->labelFactory->expects($this->once())
            ->method('forPublisher')
            ->with(new PublisherName('bruzz'))
            ->willReturn($expectedLabel);

        $this->commandBus->record();

        $this->eventBusForwardingConsumer->consume($message);

        $this->assertEquals(
            [new AddLabel('F8E5055F-66C4-4929-ABB9-822B9F5328F1', $expectedLabel)],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_will_not_add_a_label_if_no_matching_label_was_configured()
    {
        $message = new AMQPMessage(
            json_encode(
                [
                    'newsArticleId' => 'c4c19563-06e3-43fa-a15c-73a91c54b27e',
                    'eventId' => 'F8E5055F-66C4-4929-ABB9-822B9F5328F1',
                    'publisher' => 'bruzz',
                ]
            ),
            [
                'content_type' => 'application/vnd.cultuurnet.curators-api.events.news-article-about-event-added+json',
            ]
        );
        $message->delivery_info = $this->messageDeliveryInfo;

        $this->labelFactory->expects($this->once())
            ->method('forPublisher')
            ->with(new PublisherName('bruzz'))
            ->willThrowException(new InvalidArgumentException());

        $this->commandBus->record();

        $this->eventBusForwardingConsumer->consume($message);

        $this->assertEquals(
            [],
            $this->commandBus->getRecordedCommands()
        );
    }
}
