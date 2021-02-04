<?php

namespace CultuurNet\BroadwayAMQP\Message;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\BroadwayAMQP\Dummies\DummyEvent;
use CultuurNet\BroadwayAMQP\Message\Body\BodyFactoryInterface;
use CultuurNet\BroadwayAMQP\Message\Properties\PropertiesFactoryInterface;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DelegatingAMQPMessageFactoryTest extends TestCase
{
    /**
     * @var BodyFactoryInterface|MockObject
     */
    private $bodyFactory;

    /**
     * @var PropertiesFactoryInterface|MockObject
     */
    private $propertiesFactory;

    /**
     * @var DelegatingAMQPMessageFactory
     */
    private $messageFactory;

    public function setUp()
    {
        $this->bodyFactory = $this->createMock(BodyFactoryInterface::class);
        $this->propertiesFactory = $this->createMock(PropertiesFactoryInterface::class);

        $this->messageFactory = new DelegatingAMQPMessageFactory(
            $this->bodyFactory,
            $this->propertiesFactory
        );
    }

    /**
     * @test
     */
    public function it_delegates_body_and_properties_creation_to_the_respective_injected_factories()
    {
        $domainMessage = new DomainMessage(
            '06d0906d-e235-40d2-b9f3-1fa6aebc9e00',
            1,
            new Metadata(),
            new DummyEvent('06d0906d-e235-40d2-b9f3-1fa6aebc9e00', 'foo'),
            DateTime::now()
        );

        $body = '{"foo":"bar"}';
        $properties = ['deliver_mode' => 2];

        $expectedAMQPMessage = new AMQPMessage($body, $properties);

        $this->bodyFactory->expects($this->once())
            ->method('createBody')
            ->with($domainMessage)
            ->willReturn($body);

        $this->propertiesFactory->expects($this->once())
            ->method('createProperties')
            ->with($domainMessage)
            ->willReturn($properties);

        $actualAMQPMessage = $this->messageFactory->createAMQPMessage($domainMessage);

        $this->assertEquals($expectedAMQPMessage, $actualAMQPMessage);
    }
}
