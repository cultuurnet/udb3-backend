<?php

namespace CultuurNet\UDB3\EventHandling;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\EventHandling\Mock\MockLabelAdded;
use CultuurNet\UDB3\EventHandling\Mock\MockLabelRemoved;
use CultuurNet\UDB3\EventHandling\Mock\MockLabelUpdated;
use CultuurNet\UDB3\EventHandling\Mock\MockLDProjector;
use CultuurNet\UDB3\EventHandling\Mock\MockTitleTranslated;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DelegateEventHandlingToSpecificMethodTraitTest extends TestCase
{
    /**
     * @var MockObject
     */
    private $mockedMockLDProjector;

    /**
     * @var MockLDProjector
     */
    private $mockLDProjector;

    protected function setUp()
    {
        $this->mockedMockLDProjector = $this
            ->getMockBuilder(MockLDProjector::class)
            ->setMethods([
                'applyMockLabelAdded',
                'applyMockLabelUpdated',
                'applyMockLabelRemoved',
                'applyMockTitleTranslated',
            ])
            ->getMock();

        $this->mockLDProjector = $this->mockedMockLDProjector;
    }

    /**
     * @test
     */
    public function it_handles_known_event()
    {
        $domainMessage = $this->createDomainMessage(
            new MockLabelAdded()
        );

        $this->mockedMockLDProjector
            ->expects($this->once())
            ->method('applyMockLabelAdded');

        $this->mockLDProjector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_does_not_handle_event_of_the_wrong_parameter_type()
    {
        $domainMessage = $this->createDomainMessage(
            new MockLabelUpdated()
        );

        $this->mockedMockLDProjector
            ->expects($this->never())
            ->method('applyMockLabelAdded');

        $this->mockLDProjector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_does_not_handle_event_when_apply_method_has_parameter_missing()
    {
        $domainMessage = $this->createDomainMessage(
            new MockLabelRemoved()
        );

        $this->mockedMockLDProjector
            ->expects($this->never())
            ->method('applyMockLabelRemoved');

        $this->mockLDProjector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_does_not_handle_abstract_event()
    {
        $domainMessage = $this->createDomainMessage(
            new MockTitleTranslated()
        );

        $this->mockedMockLDProjector
            ->expects($this->never())
            ->method('applyMockTitleTranslated');

        $this->mockLDProjector->handle($domainMessage);
    }

    /**
     * @param mixed $payload
     * @return DomainMessage
     */
    private function createDomainMessage($payload)
    {
        return new DomainMessage(
            'id',
            1,
            new Metadata(),
            $payload,
            DateTime::now()
        );
    }
}
