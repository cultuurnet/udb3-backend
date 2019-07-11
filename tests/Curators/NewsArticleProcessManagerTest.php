<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Curators;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use Broadway\EventHandling\TraceableEventBus;
use CultuurNet\BroadwayAMQP\EventBusForwardingConsumer;
use CultuurNet\Deserializer\SimpleDeserializerLocator;
use CultuurNet\UDB3\Event\Commands\AddLabel;
use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\Silex\Curators\Events\NewsArticleAboutEventAddedJSONDeserializer;
use CultuurNet\UDB3\Silex\Curators\Events\NewsArticleAboutEventAdded;
use CultuurNet\UDB3\SimpleEventBus;
use PhpAmqpLib\Channel\AbstractChannel;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use ValueObjects\StringLiteral\StringLiteral;

final class NewsArticleProcessManagerTest extends TestCase
{
    /**
     * @var EventBusForwardingConsumer
     */
    private $eventBusForwardingConsumer;

    /**
     * @var TraceableEventBus
     */
    private $eventBus;

    /**
     * @var TraceableCommandBus
     */
    private $commandBus;

    /**
     * @var array
     */
    private $messageDeliveryInfo;

    public function setUp()
    {
        parent::setUp();

        $this->commandBus = new TraceableCommandBus();
        $this->commandBus->record();

        $processManager = new NewsArticleProcessManager(
            $this->commandBus
        );

        $this->eventBus = new TraceableEventBus(
            new SimpleEventBus()
        );
        $this->eventBus->trace();
        $this->eventBus->subscribe($processManager);

        $deserializer = new NewsArticleAboutEventAddedJSONDeserializer();
        $deserializerLocator = new SimpleDeserializerLocator();
        $deserializerLocator->registerDeserializer(
            NewsArticleAboutEventAddedJSONDeserializer::getContentType(),
            $deserializer
        );

        /* @var AMQPStreamConnection|PHPUnit_Framework_MockObject_MockObject $connection */
        $connection = $this->createMock(AMQPStreamConnection::class);

        /* @var AMQPChannel|PHPUnit_Framework_MockObject_MockObject $channel */
        $channel = $this->createMock(AMQPChannel::class);

        $connection->expects($this->any())
            ->method('channel')
            ->willReturn($channel);

        $this->eventBusForwardingConsumer = new EventBusForwardingConsumer(
            $connection,
            $this->eventBus,
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
    public function it_should_add_a_curatoren_label_to_an_event_if_a_news_article_is_created_about_it()
    {
        $message = new AMQPMessage(
            json_encode(
                [
                    'newsArticleId' => 'c4c19563-06e3-43fa-a15c-73a91c54b27e',
                    'eventId' => 'F8E5055F-66C4-4929-ABB9-822B9F5328F1',
                ]
            ),
            [
                'content_type' => 'application/vnd.cultuurnet.curators-api.events.news-article-about-event-added+json',
            ]
        );

        $message->delivery_info = $this->messageDeliveryInfo;

        $this->eventBusForwardingConsumer->consume($message);

        $expectedEvent = new NewsArticleAboutEventAdded(
            'c4c19563-06e3-43fa-a15c-73a91c54b27e',
            'F8E5055F-66C4-4929-ABB9-822B9F5328F1'
        );

        $expectedCommand = new AddLabel(
            'F8E5055F-66C4-4929-ABB9-822B9F5328F1',
            new Label('curatoren', false)
        );

        $actualEvents = $this->eventBus->getEvents();
        $actualCommands = $this->commandBus->getRecordedCommands();

        $this->assertContains($expectedEvent, $actualEvents, '', false, false, false);
        $this->assertContains($expectedCommand, $actualCommands, '', false, false, false);
    }
}
