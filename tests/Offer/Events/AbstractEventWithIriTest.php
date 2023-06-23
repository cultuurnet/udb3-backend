<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Events;

use PHPUnit\Framework\TestCase;

class AbstractEventWithIriTest extends TestCase
{
    /**
     * @var AbstractEventWithIri
     */
    protected $event;

    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    protected $iri;

    public function setUp(): void
    {
        $this->id = '1';
        $this->iri = 'event/1';
        $this->event = new MockAbstractEventWithIri($this->id, $this->iri);
    }

    /**
     * @test
     */
    public function it_returns_the_id(): void
    {
        $this->assertEquals('1', $this->event->getItemId());
    }

    /**
     * @test
     */
    public function it_returns_the_iri(): void
    {
        $this->assertEquals('event/1', $this->event->getIri());
    }

    /**
     * @test
     */
    public function it_can_be_serialized(): void
    {
        $serialized = $this->event->serialize();
        $deserialized = MockAbstractEventWithIri::deserialize($serialized);

        $this->assertEquals($this->event, $deserialized);
    }
}
