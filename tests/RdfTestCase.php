<?php

declare(strict_types=1);

namespace CultuurNet\UDB3;

use Broadway\Domain\DateTime as BroadwayDateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\RDF\GraphRepository;
use DateTime;
use EasyRdf\Serialiser\Turtle;
use PHPUnit\Framework\TestCase;

abstract class RdfTestCase extends TestCase
{
    protected GraphRepository $graphRepository;

    protected EventListener $rdfProjector;

    abstract protected function getRdfDataSetName(): string;

    protected function project(string $organizerId, array $events): void
    {
        $playhead = -1;
        $recordedOn = new DateTime('2022-12-31T12:30:15+01:00');
        foreach ($events as $event) {
            $playhead++;
            $recordedOn->modify('+1 day');
            $domainMessage = new DomainMessage(
                $organizerId,
                $playhead,
                new Metadata(),
                $event,
                BroadwayDateTime::fromString($recordedOn->format(DateTime::ATOM))
            );
            $this->rdfProjector->handle($domainMessage);
        }
    }

    protected function assertTurtleData(string $itemId, string $expectedTurtleData): void
    {
        $uri = 'https://mock.data.publiq.be/' . $this->getRdfDataSetName() . '/' . $itemId;
        $actualTurtleData = (new Turtle())->serialise($this->graphRepository->get($uri), 'turtle');
        $this->assertEquals(trim($expectedTurtleData), trim($actualTurtleData));
    }
}
