<?php

namespace CultuurNet\UDB3\Event;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Event\Commands\UpdateLocation;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Offer\IriOfferIdentifier;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Place\Events\MarkedAsCanonical;
use CultuurNet\UDB3\Place\Events\MarkedAsDuplicate;
use CultuurNet\UDB3\Search\ResultsGeneratorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use ValueObjects\Web\Url;

class LocationMarkedAsDuplicateProcessManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ResultsGeneratorInterface|MockObject
     */
    private $searchResultsGenerator;

    /**
     * @var TraceableCommandBus
     */
    private $commandBus;

    /**
     * @var LocationMarkedAsDuplicateProcessManager
     */
    private $processManager;

    protected function setUp()
    {
        $this->searchResultsGenerator = $this->createMock(ResultsGeneratorInterface::class);
        $this->commandBus = new TraceableCommandBus();

        $this->processManager = new LocationMarkedAsDuplicateProcessManager(
            $this->searchResultsGenerator,
            $this->commandBus
        );
    }

    /**
     * @test
     */
    public function it_should_only_handle_place_marked_as_duplicate_events()
    {
        $this->searchResultsGenerator->expects($this->never())
            ->method('search');

        $this->commandBus->record();

        $this->processManager->handle(
            DomainMessage::recordNow(
                '110ecece-f6b0-4360-b5c5-c95babcfe045',
                0,
                Metadata::deserialize([]),
                new MarkedAsCanonical(
                    '110ecece-f6b0-4360-b5c5-c95babcfe045',
                    'ab72ffd9-8789-4c35-b6ba-b702643a29b9'
                )
            )
        );

        $this->assertEquals([], $this->commandBus->getRecordedCommands());
    }

    /**
     * @test
     */
    public function it_updates_every_event_with_a_location_that_is_marked_as_duplicate_to_the_canonical_location()
    {
        $this->searchResultsGenerator->expects($this->once())
            ->method('search')
            ->with('location.mainId:110ecece-f6b0-4360-b5c5-c95babcfe045')
            ->willReturn([
                new IriOfferIdentifier(
                    Url::fromNative('http://www.uitdatabank.be/events/c393e98b-b33e-4948-b97a-3c48e3748398'),
                    'c393e98b-b33e-4948-b97a-3c48e3748398',
                    OfferType::EVENT()
                ),
                new IriOfferIdentifier(
                    Url::fromNative('http://www.uitdatabank.be/events/d8835de7-c84d-417b-a173-079401f29fde'),
                    'd8835de7-c84d-417b-a173-079401f29fde',
                    OfferType::EVENT()
                ),
                new IriOfferIdentifier(
                    Url::fromNative('http://www.uitdatabank.be/events/13ca4b6b-92b0-407d-b472-634dd0e654d0'),
                    '13ca4b6b-92b0-407d-b472-634dd0e654d0',
                    OfferType::EVENT()
                ),
            ]);

        $this->commandBus->record();

        $duplicateLocationId = '110ecece-f6b0-4360-b5c5-c95babcfe045';
        $canonicalLocationId = 'ab72ffd9-8789-4c35-b6ba-b702643a29b9';

        $this->processManager->handle(
            DomainMessage::recordNow(
                $duplicateLocationId,
                0,
                Metadata::deserialize([]),
                new MarkedAsDuplicate($duplicateLocationId, $canonicalLocationId)
            )
        );

        $expected = [
            new UpdateLocation('c393e98b-b33e-4948-b97a-3c48e3748398', new LocationId($canonicalLocationId)),
            new UpdateLocation('d8835de7-c84d-417b-a173-079401f29fde', new LocationId($canonicalLocationId)),
            new UpdateLocation('13ca4b6b-92b0-407d-b472-634dd0e654d0', new LocationId($canonicalLocationId)),
        ];

        $this->assertEquals($expected, $this->commandBus->getRecordedCommands());
    }
}
