<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label;

use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use Broadway\EventStore\TraceableEventStore;
use Broadway\Repository\Repository;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\EventSourcing\DBAL\UniqueConstraintException;
use CultuurNet\UDB3\Label\Events\Created;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConstraintAwareLabelServiceTest extends TestCase
{
    /**
     * @var UuidGeneratorInterface&MockObject
     */
    private $uuidGenerator;

    public function setUp(): void
    {
        $this->uuidGenerator = $this->createMock(UuidGeneratorInterface::class);

        $this->uuidGenerator->expects($this->once())
            ->method('generate')
            ->willReturn('b67d6f8b-fe08-44c9-a0a7-8e6b47dab0ff');
    }

    /**
     * @test
     */
    public function it_creates_a_new_label_aggregate_for_a_given_label_name_and_visibility(): void
    {
        $labelName = 'foo';
        $expectedUuid = new Uuid('b67d6f8b-fe08-44c9-a0a7-8e6b47dab0ff');

        $traceableEventStore = new TraceableEventStore($this->createMock(EventStore::class));
        $traceableEventStore->trace();

        $eventBus = $this->createMock(EventBus::class);

        $repository = new LabelRepository(
            $traceableEventStore,
            $eventBus
        );

        $service = $this->createService($repository);

        $returnValue = $service->createLabelAggregateIfNew(new LabelName($labelName), false);

        $this->assertEquals($expectedUuid, $returnValue);

        $this->assertEquals(
            [
                new Created(
                    $expectedUuid,
                    $labelName,
                    Visibility::invisible(),
                    Privacy::public()
                ),
            ],
            $traceableEventStore->getEvents()
        );
    }

    /**
     * @test
     */
    public function it_returns_null_if_a_label_aggregate_already_exists_with_the_same_name(): void
    {
        $labelName = new LabelName('foo');

        $repository = $this->createMock(Repository::class);

        $repository->expects($this->once())
            ->method('save')
            ->willThrowException(new UniqueConstraintException('b67d6f8b-fe08-44c9-a0a7-8e6b47dab0ff', 'foo'));

        $service = $this->createService($repository);

        $returnValue = $service->createLabelAggregateIfNew($labelName, false);

        $this->assertNull($returnValue);
    }

    private function createService(Repository $repository): ConstraintAwareLabelService
    {
        return new ConstraintAwareLabelService($repository, $this->uuidGenerator);
    }
}
