<?php

namespace CultuurNet\UDB3\Label;

use Broadway\EventHandling\EventBusInterface;
use Broadway\EventStore\EventStoreInterface;
use Broadway\EventStore\TraceableEventStore;
use Broadway\Repository\RepositoryInterface;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\EventSourcing\DBAL\UniqueConstraintException;
use CultuurNet\UDB3\Label\Events\Created;
use CultuurNet\UDB3\Label\ValueObjects\LabelName;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;

class ConstraintAwareLabelServiceTest extends TestCase
{
    /**
     * @var UuidGeneratorInterface|MockObject
     */
    private $uuidGenerator;

    public function setUp()
    {
        $this->uuidGenerator = $this->createMock(UuidGeneratorInterface::class);

        $this->uuidGenerator->expects($this->once())
            ->method('generate')
            ->willReturn('b67d6f8b-fe08-44c9-a0a7-8e6b47dab0ff');
    }

    /**
     * @test
     */
    public function it_creates_a_new_label_aggregate_for_a_given_label_name_and_visibility()
    {
        $labelName = new LabelName('foo');
        $visibility = false;
        $expectedUuid = new UUID('b67d6f8b-fe08-44c9-a0a7-8e6b47dab0ff');

        $traceableEventStore = new TraceableEventStore($this->createMock(EventStoreInterface::class));
        $traceableEventStore->trace();

        $eventBus = $this->createMock(EventBusInterface::class);

        $repository = new LabelRepository(
            $traceableEventStore,
            $eventBus
        );

        $service = $this->createService($repository);

        $returnValue = $service->createLabelAggregateIfNew($labelName, $visibility);

        $this->assertEquals($expectedUuid, $returnValue);

        $this->assertEquals(
            [
                new Created(
                    $expectedUuid,
                    $labelName,
                    Visibility::INVISIBLE(),
                    Privacy::PRIVACY_PUBLIC()
                ),
            ],
            $traceableEventStore->getEvents()
        );
    }

    /**
     * @test
     */
    public function it_returns_null_if_a_label_aggregate_already_exists_with_the_same_name()
    {
        $labelName = new LabelName('foo');

        $repository = $this->createMock(RepositoryInterface::class);

        $repository->expects($this->once())
            ->method('save')
            ->willThrowException(new UniqueConstraintException('b67d6f8b-fe08-44c9-a0a7-8e6b47dab0ff', 'foo'));

        $service = $this->createService($repository);

        $returnValue = $service->createLabelAggregateIfNew($labelName, false);

        $this->assertNull($returnValue);
    }

    /**
     * @param RepositoryInterface $repository
     * @return ConstraintAwareLabelService
     */
    private function createService(RepositoryInterface $repository)
    {
        return new ConstraintAwareLabelService($repository, $this->uuidGenerator);
    }
}
