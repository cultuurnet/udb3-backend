<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Broadway\AMQP;

use CultuurNet\UDB3\Deserializer\DeserializerLocatorInterface;
use CultuurNet\UDB3\Deserializer\DeserializerNotFoundException;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerAwareTrait;
use ValueObjects\StringLiteral\StringLiteral;

abstract class AbstractConsumer implements ConsumerInterface
{
    use LoggerAwareTrait;

    /**
     * @var AMQPStreamConnection
     */
    private $connection;

    /**
     * @var DeserializerLocatorInterface
     */
    private $deserializerLocator;

    /**
     * @var StringLiteral
     */
    private $queueName;

    /**
     * @var StringLiteral
     */
    private $exchangeName;

    /**
     * @var StringLiteral
     */
    private $consumerTag;

    /**
     * @var AMQPChannel
     */
    private $channel;

    /**
     * Seconds to delay the actual consumption of the message after it arrived.
     *
     * @var int
     */
    private $delay = 0;

    /**
     * @var string
     */
    private $messageHandlerName = 'message handler';

    /**
     * @param int $delay
     * @param string $messageHandlerName
     */
    public function __construct(
        AMQPStreamConnection $connection,
        DeserializerLocatorInterface $deserializerLocator,
        StringLiteral $consumerTag,
        StringLiteral $exchangeName,
        StringLiteral $queueName,
        $delay = 0,
        $messageHandlerName = 'message handler'
    ) {
        $this->connection = $connection;
        $this->channel = $connection->channel();
        $this->channel->basic_qos(0, 4, true);

        $this->deserializerLocator = $deserializerLocator;
        $this->queueName = $queueName;
        $this->consumerTag = $consumerTag;
        $this->exchangeName = $exchangeName;
        $this->delay = $delay;
        $this->messageHandlerName = $messageHandlerName;

        $this->declareQueue();
        $this->registerConsumeCallback();
    }


    abstract protected function handle($deserializedMessage, array $context);

    private function delayIfNecessary()
    {
        if ($this->delay > 0) {
            sleep($this->delay);
        }
    }


    public function consume(AMQPMessage $message)
    {
        $context = [];

        if ($message->has('correlation_id')) {
            $context['correlation_id'] = $message->get('correlation_id');
        }

        try {
            if ($this->logger) {
                $this->logger->info(
                    'received message with content-type ' . $message->get(
                        'content_type'
                    ),
                    $context
                );
            }

            $contentType = new StringLiteral($message->get('content_type'));

            $deserializer = $this->deserializerLocator->getDeserializerForContentType(
                $contentType
            );

            $deserializedMessage = $deserializer->deserialize(
                new StringLiteral($message->body)
            );

            $this->delayIfNecessary();

            if ($this->logger) {
                $this->logger->info(
                    'passing on message to ' . $this->messageHandlerName,
                    $context
                );
            }

            $this->handle($deserializedMessage, $context);
        } catch (DeserializerNotFoundException $e) {
            $message->delivery_info['channel']->basic_ack(
                $message->delivery_info['delivery_tag']
            );
            if ($this->logger) {
                $this->logger->info(
                    'auto acknowledged message because no deserializer was configured for it',
                    $context
                );
            }

            return;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error(
                    $e->getMessage(),
                    $context + ['exception' => $e]
                );
            }

            $message->delivery_info['channel']->basic_reject(
                $message->delivery_info['delivery_tag'],
                false
            );

            if ($this->logger) {
                $this->logger->info(
                    'message rejected',
                    $context
                );
            }

            return;
        }

        $message->delivery_info['channel']->basic_ack(
            $message->delivery_info['delivery_tag']
        );

        if ($this->logger) {
            $this->logger->info(
                'message acknowledged',
                $context
            );
        }
    }

    private function declareQueue()
    {
        $this->channel->queue_declare(
            (string) $this->queueName,
            $passive = false,
            $durable = true,
            $exclusive = false,
            $autoDelete = false
        );

        $this->channel->queue_bind(
            $this->queueName,
            $this->exchangeName,
            $routingKey = '#'
        );
    }

    private function registerConsumeCallback()
    {
        $this->channel->basic_consume(
            $this->queueName,
            $consumerTag = (string) $this->consumerTag,
            $noLocal = false,
            $noAck = false,
            $exclusive = false,
            $noWait = false,
            [$this, 'consume']
        );
    }

    /**
     * @return AMQPStreamConnection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @return AMQPChannel
     */
    public function getChannel()
    {
        return $this->channel;
    }
}
