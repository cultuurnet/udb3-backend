<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Broadway\AMQP;

use Broadway\Domain\DomainEventStream;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventBus;
use CultuurNet\UDB3\Deserializer\DeserializerInterface;
use CultuurNet\UDB3\Deserializer\DeserializerLocatorInterface;
use CultuurNet\UDB3\Deserializer\DeserializerNotFoundException;
use CultuurNet\UDB3\Model\ValueObject\Identity\UuidFactory\GeneratedUuidFactory;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class EventBusForwardingConsumerTest extends TestCase
{
    private AMQPStreamConnection&MockObject $connection;

    private string $queueName;

    private string $exchangeName;

    private string $consumerTag;

    private EventBus&MockObject $eventBus;

    private DeserializerLocatorInterface&MockObject $deserializerLocator;

    private AMQPChannel&MockObject $channel;

    private EventBusForwardingConsumer $eventBusForwardingConsumer;

    private LoggerInterface&MockObject $logger;

    private DeserializerInterface&MockObject $deserializer;


    public function setUp(): void
    {
        $this->connection = $this->createMock(AMQPStreamConnection::class);

        $delay = 1;

        $this->queueName = 'my-queue';
        $this->exchangeName = 'my-exchange';
        $this->consumerTag = 'my-tag';
        $this->eventBus = $this->createMock(EventBus::class);
        $this->deserializerLocator = $this->createMock(DeserializerLocatorInterface::class);
        $this->channel = $this->getMockBuilder(AMQPChannel::class)
            ->disableOriginalConstructor()
            ->disableProxyingToOriginalMethods()
            ->getMock();

        $this->connection->expects($this->any())
            ->method('channel')
            ->willReturn($this->channel);

        $this->eventBusForwardingConsumer = new EventBusForwardingConsumer(
            $this->connection,
            $this->eventBus,
            $this->deserializerLocator,
            $this->consumerTag,
            $this->exchangeName,
            $this->queueName,
            new GeneratedUuidFactory(),
            $delay
        );

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->eventBusForwardingConsumer->setLogger($this->logger);

        $this->deserializer = $this->createMock(DeserializerInterface::class);
    }

    /**
     * @test
     */
    public function it_can_get_the_connection(): void
    {
        $this->channel->expects($this->once())
            ->method('basic_qos')
            ->with(0, 4, true);

        $eventBusForwardingConsumer = new EventBusForwardingConsumer(
            $this->connection,
            $this->eventBus,
            $this->deserializerLocator,
            $this->consumerTag,
            $this->exchangeName,
            $this->queueName,
            new GeneratedUuidFactory()
        );

        $expectedConnection = $this->connection;

        $this->assertEquals($expectedConnection, $eventBusForwardingConsumer->getConnection());
    }

    /**
     * @test
     */
    public function it_can_publish_the_message_on_the_event_bus(): void
    {
        $context = [];
        $context['correlation_id'] = 'my-correlation-id-123';

        $expectedMetadata = new Metadata($context);
        $expectedPayload = '';

        $this->eventBus->expects($this->once())
            ->method('publish')
            ->with($this->callback(
                function ($domainEventStream) use ($expectedMetadata, $expectedPayload) {
                    /** @var DomainEventStream $domainEventStream */
                    $iterator = $domainEventStream->getIterator();
                    $domainMessage = $iterator->offsetGet(0);
                    $actualMetadata = $domainMessage->getMetadata();
                    $actualPayload = $domainMessage->getPayload();
                    if ($actualMetadata == $expectedMetadata && $actualPayload == $expectedPayload) {
                        return true;
                    } else {
                        return false;
                    }
                }
            ));

        $this->deserializerLocator->expects($this->once())
            ->method('getDeserializerForContentType')
            ->with('application/vnd.cultuurnet.udb3-events.dummy-event+json')
            ->willReturn($this->deserializer);

        $this->deserializer->expects($this->once())
            ->method('deserialize')
            ->with('')
            ->willReturn('');

        $this->channel->expects($this->once())
            ->method('basic_ack')
            ->with('my-delivery-tag');

        $messageProperties = [
            'content_type' => 'application/vnd.cultuurnet.udb3-events.dummy-event+json',
            'correlation_id' => 'my-correlation-id-123',
        ];

        $messageBody = '';

        $message = new AMQPMessage($messageBody, $messageProperties);
        $message->delivery_info['channel'] = $this->channel;
        $message->delivery_info['delivery_tag'] = 'my-delivery-tag';

        $this->eventBusForwardingConsumer->consume($message);
    }

    /**
     * @test
     */
    public function it_logs_messages_when_consuming(): void
    {
        $context = [];
        $context['correlation_id'] = 'my-correlation-id-123';

        $this->logger->expects($this->exactly(3))
            ->method('info')
            ->withConsecutive(
                [
                    'received message with content-type application/vnd.cultuurnet.udb3-events.dummy-event+json',
                    $context,
                ],
                [
                    'passing on message to event bus',
                    $context,
                ],
                [
                    'message acknowledged',
                    $context,
                ]
            );

        $this->deserializerLocator->expects($this->once())
            ->method('getDeserializerForContentType')
            ->with('application/vnd.cultuurnet.udb3-events.dummy-event+json')
            ->willReturn($this->deserializer);

        $messageProperties = [
            'content_type' => 'application/vnd.cultuurnet.udb3-events.dummy-event+json',
            'correlation_id' => 'my-correlation-id-123',
        ];

        $messageBody = '';

        $message = new AMQPMessage($messageBody, $messageProperties);
        $message->delivery_info['channel'] = $this->channel;
        $message->delivery_info['delivery_tag'] = 'my-delivery-tag';

        $this->eventBusForwardingConsumer->consume($message);
    }

    /**
     * @test
     */
    public function it_rejects_the_massage_when_an_error_occurs(): void
    {
        $this->deserializerLocator->expects($this->once())
            ->method('getDeserializerForContentType')
            ->with('application/vnd.cultuurnet.udb3-events.dummy-event+json')
            ->willThrowException(new \InvalidArgumentException('Deserializerlocator error'));

        $this->channel->expects($this->once())
            ->method('basic_reject')
            ->with('my-delivery-tag');

        $messageProperties = [
            'content_type' => 'application/vnd.cultuurnet.udb3-events.dummy-event+json',
            'correlation_id' => 'my-correlation-id-123',
        ];

        $messageBody = '';

        $message = new AMQPMessage($messageBody, $messageProperties);
        $message->delivery_info['channel'] = $this->channel;
        $message->delivery_info['delivery_tag'] = 'my-delivery-tag';

        $this->eventBusForwardingConsumer->consume($message);
    }

    /**
     * @test
     */
    public function it_logs_messages_when_rejecting_a_message(): void
    {
        $context = [];
        $context['correlation_id'] = 'my-correlation-id-123';

        $this->logger
            ->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                [
                    'received message with content-type application/vnd.cultuurnet.udb3-events.dummy-event+json',
                    $context,
                ],
                [
                    'message rejected',
                    $context,
                ]
            );

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(
                'Deserializerlocator error',
                $context + ['exception' => new \InvalidArgumentException('Deserializerlocator error')]
            );

        $this->deserializerLocator->expects($this->once())
            ->method('getDeserializerForContentType')
            ->with('application/vnd.cultuurnet.udb3-events.dummy-event+json')
            ->willThrowException(new \InvalidArgumentException('Deserializerlocator error'));

        $this->channel->expects($this->once())
            ->method('basic_reject')
            ->with('my-delivery-tag');

        $messageProperties = [
            'content_type' => 'application/vnd.cultuurnet.udb3-events.dummy-event+json',
            'correlation_id' => 'my-correlation-id-123',
        ];

        $messageBody = '';

        $message = new AMQPMessage($messageBody, $messageProperties);
        $message->delivery_info['channel'] = $this->channel;
        $message->delivery_info['delivery_tag'] = 'my-delivery-tag';

        $this->eventBusForwardingConsumer->consume($message);
    }

    /**
     * @test
     */
    public function it_automatically_acknowledges_when_no_deserializer_was_found(): void
    {
        $this->deserializerLocator->expects($this->once())
            ->method('getDeserializerForContentType')
            ->with('application/vnd.cultuurnet.udb3-events.dummy-event+json')
            ->willThrowException(new DeserializerNotFoundException());

        $this->channel->expects($this->once())
            ->method('basic_ack')
            ->with('my-delivery-tag');

        $messageProperties = [
            'content_type' => 'application/vnd.cultuurnet.udb3-events.dummy-event+json',
            'correlation_id' => 'my-correlation-id-123',
        ];

        $messageBody = '';

        $message = new AMQPMessage($messageBody, $messageProperties);
        $message->delivery_info['channel'] = $this->channel;
        $message->delivery_info['delivery_tag'] = 'my-delivery-tag';

        $this->eventBusForwardingConsumer->consume($message);
    }
}
