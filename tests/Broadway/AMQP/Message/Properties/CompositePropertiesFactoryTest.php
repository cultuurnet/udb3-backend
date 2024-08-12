<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Broadway\AMQP\Message\Properties;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CompositePropertiesFactoryTest extends TestCase
{
    /**
     * @var PropertiesFactoryInterface&MockObject
     */
    private $mockFactory1;

    /**
     * @var PropertiesFactoryInterface&MockObject
     */
    private $mockFactory2;

    /**
     * @var CompositePropertiesFactory
     */
    private $compositeFactory;

    public function setUp(): void
    {
        $this->mockFactory1 = $this->createMock(PropertiesFactoryInterface::class);
        $this->mockFactory2 = $this->createMock(PropertiesFactoryInterface::class);

        $this->compositeFactory = (new CompositePropertiesFactory())
            ->with($this->mockFactory1)
            ->with($this->mockFactory2);
    }

    /**
     * @test
     */
    public function it_combines_properties_from_all_injected_property_factories(): void
    {
        $domainMessage = new DomainMessage(
            '7a8ccbc5-d802-46c8-b9ec-7a286bc7653b',
            0,
            new Metadata(),
            new \stdClass(),
            DateTime::now()
        );

        $this->mockFactory1->expects($this->once())
            ->method('createProperties')
            ->with($domainMessage)
            ->willReturn(
                [
                    'correlation_id' => '123456',
                    'content_type' => 'text/plain',
                ]
            );

        $this->mockFactory2->expects($this->once())
            ->method('createProperties')
            ->with($domainMessage)
            ->willReturn(
                [
                    'content_type' => 'application/ld+json',
                    'delivery_mode' => 2,
                ]
            );

        $expectedProperties = [
            'correlation_id' => '123456',
            'content_type' => 'application/ld+json',
            'delivery_mode' => 2,
        ];

        $actualProperties = $this->compositeFactory->createProperties($domainMessage);

        $this->assertEquals($expectedProperties, $actualProperties);
    }
}
