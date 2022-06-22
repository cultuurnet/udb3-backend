<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Label;

use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Entity;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Query;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Http\Label\Query\QueryFactory;
use CultuurNet\UDB3\Http\Label\Query\QueryFactoryInterface;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use CultuurNet\UDB3\StringLiteral;

final class ReadRestControllerTest extends TestCase
{
    private Entity $entity;

    private Request $request;

    private Query $query;

    /**
     * @var ReadRepositoryInterface|MockObject
     */
    private $labelRepository;

    /**
     * @var QueryFactoryInterface|MockObject
     */
    private $queryFactory;

    private ReadRestController $readRestController;

    protected function setUp(): void
    {
        $this->entity = new Entity(
            new UUID('b88f2756-a1d8-4377-a36a-59662fc02d98'),
            new LabelName('labelName'),
            Visibility::INVISIBLE(),
            Privacy::PRIVACY_PRIVATE()
        );

        $this->request = new Request([
            QueryFactory::QUERY => 'label',
            QueryFactory::START => 5,
            QueryFactory::LIMIT => 2,
        ]);

        $this->query = new Query(
            'label',
            'userId',
            5,
            2
        );

        $this->labelRepository = $this->createMock(ReadRepositoryInterface::class);
        $this->mockGetByUuid();
        $this->mockGetByName();
        $this->mockSearch();
        $this->mockSearchTotalLabels();

        $this->queryFactory = $this->createMock(QueryFactory::class);
        $this->mockCreateQuery();

        $this->readRestController = new ReadRestController(
            $this->labelRepository,
            $this->queryFactory
        );
    }

    /**
     * @test
     */
    public function it_returns_json_response_for_get_by_uuid(): void
    {
        $jsonResponse = $this->readRestController->get(
            $this->entity->getUuid()->toString()
        );

        $expectedJsonResponse = new JsonResponse(
            $this->entityToArray($this->entity)
        );

        $this->assertEquals($expectedJsonResponse, $jsonResponse);
    }

    /**
     * @test
     */
    public function it_should_return_a_json_response_when_you_get_a_label_by_name(): void
    {
        $this->labelRepository
            ->expects($this->never())
            ->method('getByUuid');

        $jsonResponse = $this->readRestController->get(
            $this->entity->getName()->toString()
        );

        $expectedJsonResponse = new JsonResponse(
            $this->entityToArray($this->entity)
        );

        $this->assertEquals($expectedJsonResponse, $jsonResponse);
    }

    /**
     * @test
     */
    public function it_returns_json_response_for_search(): void
    {
        $jsonResponse = $this->readRestController->search($this->request);

        $expectedJson = [
            '@context' => 'http://www.w3.org/ns/hydra/context.jsonld',
            '@type' => 'PagedCollection',
            'itemsPerPage' => 2,
            'totalItems' => 2,
            'member' => [
                $this->entityToArray($this->entity),
                $this->entityToArray($this->entity),
            ],
        ];

        $this->assertEquals($expectedJson, Json::decodeAssociatively((string) $jsonResponse->getBody()));
    }

    /**
     * @test
     */
    public function it_returns_an_empty_collection_when_no_labels_are_found(): void
    {
        $readService = $this->createMock(ReadRepositoryInterface::class);

        $readService->method('searchTotalLabels')
            ->with($this->query)
            ->willReturn(0);

        $readService->method('search')
            ->with($this->query)
            ->willReturn([]);

        $readRestController = new ReadRestController(
            $readService,
            $this->queryFactory
        );

        $jsonResponse = $readRestController->search($this->request);

        $expectedJson = [
            '@context' => 'http://www.w3.org/ns/hydra/context.jsonld',
            '@type' => 'PagedCollection',
            'itemsPerPage' => 2,
            'totalItems' => 0,
            'member' => [],
        ];

        $this->assertEquals(200, $jsonResponse->getStatusCode());
        $this->assertEquals($expectedJson, Json::decodeAssociatively((string) $jsonResponse->getBody()));
    }

    private function mockGetByUuid(): void
    {
        $this->labelRepository->method('getByUuid')
            ->with($this->entity->getUuid())
            ->willReturn($this->entity);
    }

    private function mockGetByName(): void
    {
        $this->labelRepository->method('getByName')
            ->with($this->entity->getName()->toString())
            ->willReturn($this->entity);
    }

    private function mockSearch(): void
    {
        $this->labelRepository->method('search')
            ->with($this->query)
            ->willReturn([$this->entity, $this->entity]);
    }

    private function mockSearchTotalLabels(): void
    {
        $this->labelRepository->method('searchTotalLabels')
            ->with($this->query)
            ->willReturn(2);
    }

    private function mockCreateQuery(): void
    {
        $this->queryFactory->method('createFromRequest')
            ->with($this->request)
            ->willReturn($this->query);
    }

    private function entityToArray(Entity $entity): array
    {
        return [
            'uuid' => $entity->getUuid()->toString(),
            'name' => $entity->getName()->toString(),
            'visibility' => $entity->getVisibility()->toString(),
            'privacy' => $entity->getPrivacy()->toString(),
            'excluded' => $entity->isExcluded(),
        ];
    }
}
