<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Curators;

use CultuurNet\BroadwayAMQP\EventBusForwardingConsumer;
use CultuurNet\Deserializer\SimpleDeserializerLocator;
use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\Offer\OfferEditingServiceInterface;
use CultuurNet\UDB3\Curators\Events\NewsArticleAboutEventAddedJSONDeserializer;
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
     * @var OfferEditingServiceInterface|MockObject
     */
    private $offerEditingService;

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

        $this->offerEditingService = $this->createMock(OfferEditingServiceInterface::class);
        $this->labelFactory = $this->createMock(LabelFactory::class);

        $processManager = new NewsArticleProcessManager(
            $this->offerEditingService,
            $this->labelFactory
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
            ->with(Publisher::bruzz())
            ->willReturn($expectedLabel);

        $this->offerEditingService->expects($this->once())
            ->method('addLabel')
            ->with(
                'F8E5055F-66C4-4929-ABB9-822B9F5328F1',
                $expectedLabel
            );

        $this->eventBusForwardingConsumer->consume($message);
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
            ->with(Publisher::bruzz())
            ->willThrowException(new InvalidArgumentException());

        $this->offerEditingService->expects($this->never())->method('addLabel');

        $this->eventBusForwardingConsumer->consume($message);
    }
}
