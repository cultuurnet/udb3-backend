<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Broadway\AMQP;

use Broadway\Domain\DateTime as BroadwayDateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Broadway\AMQP\DomainMessage\SpecificationInterface;
use CultuurNet\UDB3\Broadway\AMQP\Dummies\DummyEvent;
use CultuurNet\UDB3\Broadway\AMQP\Dummies\DummyEventNotSerializable;
use CultuurNet\UDB3\Broadway\AMQP\Message\Body\PayloadOnlyBodyFactory;
use CultuurNet\UDB3\Broadway\AMQP\Message\DelegatingAMQPMessageFactory;
use CultuurNet\UDB3\Broadway\AMQP\Message\Properties\CorrelationIdPropertiesFactory;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Class AMQPPublisherTest
 * @package CultuurNet\BroadwayAMQP
 */
class AMQPPublisherTest extends TestCase
{
    /**
     * @var AMQPChannel|MockObject
     */
    private $amqpChannel;

    /**
     * @var SpecificationInterface|MockObject
     */
    private $specification;

    /**
     * @var AMQPPublisher
     */
    private $amqpPublisher;

    /**
     * @var DomainMessage
     */
    private $domainMessage;

    /**
     * @var DelegatingAMQPMessageFactory
     */
    private $messageFactory;

    protected function setUp()
    {
        $this->amqpChannel = $this->createMock(AMQPChannel::class);

        $this->specification = $this->createMock(SpecificationInterface::class);

        $this->messageFactory = new DelegatingAMQPMessageFactory(
            new PayloadOnlyBodyFactory(),
            new CorrelationIdPropertiesFactory()
        );

        $this->amqpPublisher = new AMQPPublisher(
            $this->amqpChannel,
            'exchange_name',
            $this->specification,
            $this->messageFactory
        );

        $this->domainMessage = new DomainMessage(
            'F68E71A1-DBB0-4542-AEE5-BD937E095F74',
            2,
            new Metadata(),
            new DummyEvent(
                'F68E71A1-DBB0-4542-AEE5-BD937E095F74',
                'test 123 456'
            ),
            BroadwayDateTime::fromString('2015-01-02T08:40:00+0100')
        );
    }

    /**
     * @test
     */
    public function it_does_publish_a_domain_message_when_specification_is_satisfied()
    {
        $this->expectSpecificationIsSatisfied();

        $expectedBody = '{"id":"F68E71A1-DBB0-4542-AEE5-BD937E095F74","content":"test 123 456"}';
        $expectedProperties = ['correlation_id' => 'F68E71A1-DBB0-4542-AEE5-BD937E095F74-2'];

        $expectedMessage = new AMQPMessage($expectedBody, $expectedProperties);

        $this->amqpChannel->expects($this->once())
            ->method('basic_publish')
            ->with($expectedMessage, 'exchange_name', null);

        $this->amqpPublisher->handle($this->domainMessage);
    }

    /**
     * @test
     */
    public function it_does_not_publish_a_domain_message_when_specification_is_not_satisfied()
    {
        $this->expectSpecificationIsNotSatisfied();

        $this->amqpChannel->expects($this->never())
            ->method('basic_publish');

        $this->amqpPublisher->handle($this->domainMessage);
    }

    /**
     * @test
     */
    public function it_throws_runtime_exception_when_payload_is_not_serializable()
    {
        $this->expectSpecificationIsSatisfied();

        $domainMessage = new DomainMessage(
            'F68E71A1-DBB0-4542-AEE5-BD937E095F74',
            2,
            new Metadata(),
            new DummyEventNotSerializable(
                'F68E71A1-DBB0-4542-AEE5-BD937E095F74',
                'test 123 456'
            ),
            BroadwayDateTime::fromString('2015-01-02T08:40:00+0100')
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Unable to serialize CultuurNet\UDB3\Broadway\AMQP\Dummies\DummyEventNotSerializable'
        );

        $this->amqpPublisher->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_logs_a_message_when_publishing()
    {
        $this->expectSpecificationIsSatisfied();

        /** @var LoggerInterface|MockObject $logger */
        $logger = $this->createMock(LoggerInterface::class);
        $this->amqpPublisher->setLogger($logger);

        $logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'publishing message with event type CultuurNet\UDB3\Broadway\AMQP\Dummies\DummyEvent to exchange exchange_name'
            );

        $this->amqpPublisher->handle($this->domainMessage);
    }

    /**
     * @test
     */
    public function it_logs_a_message_when_specification_is_not_satisfied()
    {
        $this->expectSpecificationIsNotSatisfied();

        /** @var LoggerInterface|MockObject $logger */
        $logger = $this->createMock(LoggerInterface::class);
        $this->amqpPublisher->setLogger($logger);

        $logger
            ->expects($this->once())
            ->method('warning')
            ->with('message was skipped by specification ' . get_class($this->specification));

        $this->amqpPublisher->handle($this->domainMessage);
    }

    private function expectSpecificationIsSatisfied()
    {
        $this->specification->expects($this->any())
            ->method('isSatisfiedBy')
            ->willReturn(true);
    }

    private function expectSpecificationIsNotSatisfied()
    {
        $this->specification->expects($this->any())
            ->method('isSatisfiedBy')
            ->willReturn(false);
    }
}
