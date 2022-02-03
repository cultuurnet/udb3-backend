<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Label;

use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Entity;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Query;
use CultuurNet\UDB3\Label\Services\ReadServiceInterface;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Http\Label\Query\QueryFactory;
use CultuurNet\UDB3\Http\Label\Query\QueryFactoryInterface;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use ValueObjects\Number\Natural;
use ValueObjects\StringLiteral\StringLiteral;

class ReadRestControllerTest extends TestCase
{
    /**
     * @var Entity
     */
    private $entity;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Query
     */
    private $query;

    /**
     * @var ReadServiceInterface|MockObject
     */
    private $readService;

    /**
     * @var QueryFactoryInterface|MockObject
     */
    private $queryFactory;

    /**
     * @var ReadRestController
     */
    private $readRestController;

    protected function setUp()
    {
        $this->entity = new Entity(
            new UUID('b88f2756-a1d8-4377-a36a-59662fc02d98'),
            new StringLiteral('labelName'),
            Visibility::INVISIBLE(),
            Privacy::PRIVACY_PRIVATE()
        );

        $this->request = new Request([
            QueryFactory::QUERY => 'label',
            QueryFactory::START => 5,
            QueryFactory::LIMIT => 2,
        ]);

        $this->query = new Query(
            new StringLiteral('label'),
            new StringLiteral('userId'),
            new Natural(5),
            new Natural(2)
        );

        $this->readService = $this->createMock(ReadServiceInterface::class);
        $this->mockGetByUuid();
        $this->mockGetByName();
        $this->mockSearch();
        $this->mockSearchTotalLabels();

        $this->queryFactory = $this->createMock(QueryFactory::class);
        $this->mockCreateQuery();

        $this->readRestController = new ReadRestController(
            $this->readService,
            $this->queryFactory
        );
    }

    /**
     * @test
     */
    public function it_returns_json_response_for_get_by_uuid()
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
    public function it_should_return_a_json_response_when_you_get_a_label_by_name()
    {
        $this->readService
            ->expects($this->never())
            ->method('getByUuid');

        $jsonResponse = $this->readRestController->get(
            (string) $this->entity->getName()
        );

        $expectedJsonResponse = new JsonResponse(
            $this->entityToArray($this->entity)
        );

        $this->assertEquals($expectedJsonResponse, $jsonResponse);
    }

    /**
     * @test
     */
    public function it_returns_json_response_for_search()
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

        $this->assertEquals($expectedJson, json_decode((string) $jsonResponse->getBody(), true));
    }

    /**
     * @test
     */
    public function it_returns_an_empty_collection_when_no_labels_are_found(): void
    {
        $readService = $this->createMock(ReadServiceInterface::class);

        $readService->method('searchTotalLabels')
            ->with($this->query)
            ->willReturn(new Natural(0));

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
        $this->assertEquals($expectedJson, json_decode((string) $jsonResponse->getBody(), true));
    }

    private function mockGetByUuid()
    {
        $this->readService->method('getByUuid')
            ->with($this->entity->getUuid()->toString())
            ->willReturn($this->entity);
    }

    private function mockGetByName()
    {
        $this->readService->method('getByName')
            ->with($this->entity->getName()->toNative())
            ->willReturn($this->entity);
    }

    private function mockSearch()
    {
        $this->readService->method('search')
            ->with($this->query)
            ->willReturn([$this->entity, $this->entity]);
    }

    private function mockSearchTotalLabels()
    {
        $this->readService->method('searchTotalLabels')
            ->with($this->query)
            ->willReturn(new Natural(2));
    }

    private function mockCreateQuery()
    {
        $this->queryFactory->method('createFromRequest')
            ->with($this->request)
            ->willReturn($this->query);
    }

    /**
     * @return array
     */
    private function entityToArray(Entity $entity)
    {
        return [
            'uuid' => $entity->getUuid()->toString(),
            'name' => $entity->getName()->toNative(),
            'visibility' => $entity->getVisibility()->toNative(),
            'privacy' => $entity->getPrivacy()->toNative(),
        ];
    }
}
