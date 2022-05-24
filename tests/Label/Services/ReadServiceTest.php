<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\Services;

use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Entity;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Query;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use CultuurNet\UDB3\StringLiteral;

final class ReadServiceTest extends TestCase
{
    /**
     * @var ReadRepositoryInterface|MockObject
     */
    private $readRepository;

    private ReadServiceInterface $readService;


    private Entity $entity;

    private Query $query;

    protected function setUp(): void
    {
        $this->entity = new Entity(
            new UUID('749bc3dd-9d9b-4b4f-b5ab-9cc03fc7f669'),
            new StringLiteral('labelName'),
            Visibility::INVISIBLE(),
            Privacy::PRIVACY_PRIVATE()
        );

        $this->query = new Query('something');

        $this->readRepository = $this->createMock(ReadRepositoryInterface::class);
        $this->mockGetByUuid();
        $this->mockGetByName();
        $this->mockSearch();
        $this->mockSearchTotalLabels();

        $this->readService = new ReadService(
            $this->readRepository
        );
    }

    /**
     * @test
     */
    public function it_can_get_label_entity_based_on_uuid(): void
    {
        $this->readRepository->expects($this->once())
            ->method('getByUuid')
            ->with($this->entity->getUuid());

        $entity = $this->readService->getByUuid($this->entity->getUuid());

        $this->assertEquals($this->entity, $entity);
    }

    /**
     * @test
     */
    public function it_can_get_label_entity_by_name(): void
    {
        $this->readRepository->expects($this->once())
            ->method('getByName')
            ->with($this->entity->getName());

        $entity = $this->readService->getByName($this->entity->getName());

        $this->assertEquals($this->entity, $entity);
    }

    /**
     * @test
     */
    public function it_can_get_label_based_on_query(): void
    {
        $this->readRepository->expects($this->once())
            ->method('search')
            ->with($this->query);

        $entities = $this->readService->search($this->query);

        $this->assertEquals([$this->entity, $this->entity], $entities);
    }

    /**
     * @test
     */
    public function it_can_get_total_labels_count_based_on_query(): void
    {
        $this->readRepository->expects($this->once())
            ->method('searchTotalLabels')
            ->with($this->query);

        $totalLabels = $this->readService->searchTotalLabels($this->query);

        $this->assertEquals(10, $totalLabels);
    }

    private function mockGetByUuid(): void
    {
        $this->readRepository->method('getByUuid')
            ->with($this->entity->getUuid())
            ->willReturn($this->entity);
    }

    private function mockGetByName(): void
    {
        $this->readRepository->method('getByName')
            ->with($this->entity->getName())
            ->willReturn($this->entity);
    }

    private function mockSearch(): void
    {
        $this->readRepository->method('search')
            ->with($this->query)
            ->willReturn([$this->entity, $this->entity]);
    }

    private function mockSearchTotalLabels(): void
    {
        $this->readRepository->method('searchTotalLabels')
            ->with($this->query)
            ->willReturn(10);
    }
}
