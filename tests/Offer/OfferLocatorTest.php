<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer;

use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OfferLocatorTest extends TestCase
{
    /**
     * @var IriGeneratorInterface&MockObject
     */
    protected $iriGenerator;

    public function setUp(): void
    {
        $this->iriGenerator = $this->createMock(IriGeneratorInterface::class);
    }

    /**
     * @test
     */
    public function it_should_add_the_location_of_an_offer_to_its_metadata_as_id(): void
    {
        $this->iriGenerator
            ->method('iri')
            ->with('9B750422-A090-4DCD-AF8F-1C94F0329E3C')
            ->willReturn('https://du.de/offer/9B750422-A090-4DCD-AF8F-1C94F0329E3C');

        $locator = new OfferLocator($this->iriGenerator);

        $eventStream = $this->createDomainEventStream();

        $newEventStream = $locator->decorateForWrite('type', '9B750422-A090-4DCD-AF8F-1C94F0329E3C', $eventStream);

        $messages = iterator_to_array($newEventStream);

        $this->assertCount(2, $messages);

        $expectedMetadata = new Metadata(['bar' => 1337, 'id' => 'https://du.de/offer/9B750422-A090-4DCD-AF8F-1C94F0329E3C']);

        foreach ($messages as $message) {
            $this->assertEquals($expectedMetadata, $message->getMetadata());
        }
    }

    private function createDomainEventStream(): DomainEventStream
    {
        $m1 = DomainMessage::recordNow('id', 42, Metadata::kv('bar', 1337), 'payload');
        $m2 = DomainMessage::recordNow('id', 42, Metadata::kv('bar', 1337), 'payload');

        return new DomainEventStream([$m1, $m2]);
    }
}
