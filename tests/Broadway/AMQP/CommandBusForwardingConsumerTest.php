<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Broadway\AMQP;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Deserializer\DeserializerInterface;
use CultuurNet\UDB3\Deserializer\DeserializerLocatorInterface;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class CommandBusForwardingConsumerTest extends TestCase
{
    /**
     * @var CommandBus&MockObject
     */
    private $commandBus;

    /**
     * @var DeserializerLocatorInterface&MockObject
     */
    private $deserializerLocator;

    /**
     * @var AMQPChannel&MockObject
     */
    private $channel;

    /**
     * Seconds to delay the actual consumption of the message after it arrived.
     */
    private int $delay;


    private CommandBusForwardingConsumer $commandBusForwardingConsumer;

    /**
     * @var DeserializerInterface&MockObject
     */
    private $deserializer;


    public function setUp(): void
    {
        $connection = $this->createMock(AMQPStreamConnection::class);

        $this->delay = 1;

        $queueName = 'my-queue';
        $exchangeName = 'my-exchange';
        $consumerTag = 'my-tag';
        $this->commandBus = $this->createMock(CommandBus::class);
        $this->deserializerLocator = $this->createMock(DeserializerLocatorInterface::class);
        $this->channel = $this->getMockBuilder(AMQPChannel::class)
            ->disableOriginalConstructor()
            ->disableProxyingToOriginalMethods()
            ->getMock();

        $connection->expects($this->any())
            ->method('channel')
            ->willReturn($this->channel);

        $this->commandBusForwardingConsumer = new CommandBusForwardingConsumer(
            $connection,
            $this->commandBus,
            $this->deserializerLocator,
            $consumerTag,
            $exchangeName,
            $queueName,
            $this->delay
        );

        $logger = $this->createMock(LoggerInterface::class);
        $this->commandBusForwardingConsumer->setLogger($logger);

        $this->deserializer = $this->createMock(DeserializerInterface::class);
    }

    /**
     * @test
     */
    public function it_can_dispatch_the_message_on_the_command_bus(): void
    {
        $expectedCommand = new \stdClass();
        $expectedCommand->foo = 'bar';

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($expectedCommand);

        $this->deserializerLocator->expects($this->once())
            ->method('getDeserializerForContentType')
            ->with('application/vnd.cultuurnet.udb3-commands.dummy-command+json')
            ->willReturn($this->deserializer);

        $this->deserializer->expects($this->once())
            ->method('deserialize')
            ->with('')
            ->willReturn($expectedCommand);

        $this->channel->expects($this->once())
            ->method('basic_ack')
            ->with('my-delivery-tag');

        $messageProperties = [
            'content_type' => 'application/vnd.cultuurnet.udb3-commands.dummy-command+json',
            'correlation_id' => 'my-correlation-id-123',
        ];

        $messageBody = '';

        $message = new AMQPMessage($messageBody, $messageProperties);
        $message->delivery_info['channel'] = $this->channel;
        $message->delivery_info['delivery_tag'] = 'my-delivery-tag';

        $this->commandBusForwardingConsumer->consume($message);
    }
}
