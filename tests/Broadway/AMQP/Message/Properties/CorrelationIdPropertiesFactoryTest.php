<?php

namespace CultuurNet\UDB3\Broadway\AMQP\Message\Properties;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use PHPUnit\Framework\TestCase;

class CorrelationIdPropertiesFactoryTest extends TestCase
{
    /**
     * @var CorrelationIdPropertiesFactory
     */
    private $factory;

    public function setUp()
    {
        $this->factory = new CorrelationIdPropertiesFactory();
    }

    /**
     * @test
     */
    public function it_determines_correlation_id_based_on_message_id_and_playhead()
    {
        $id = 'effa2456-de78-480c-90ef-eb0a02b687c8';
        $playhead = 3;

        $domainMessage = new DomainMessage($id, $playhead, new Metadata(), new \stdClass(), DateTime::now());

        $expectedProperties = ['correlation_id' => 'effa2456-de78-480c-90ef-eb0a02b687c8-3'];
        $actualProperties = $this->factory->createProperties($domainMessage);

        $this->assertEquals($expectedProperties, $actualProperties);
    }
}
