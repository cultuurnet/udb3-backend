<?php

namespace CultuurNet\UDB3\Symfony\Label;

use CultuurNet\Hydra\PagedCollection;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Entity;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Query;
use CultuurNet\UDB3\Label\Services\ReadServiceInterface;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Symfony\Label\Query\QueryFactory;
use CultuurNet\UDB3\Symfony\Label\Query\QueryFactoryInterface;
use CultuurNet\UDB3\Symfony\Management\User\UserIdentificationInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use ValueObjects\Identity\UUID;
use ValueObjects\Number\Natural;
use ValueObjects\StringLiteral\StringLiteral;

class ReadRestControllerTest extends \PHPUnit_Framework_TestCase
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
     * @var ReadServiceInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $readService;

    /**
     * @var UserIdentificationInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $userIdentification;

    /**
     * @var QueryFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $queryFactory;

    /**
     * @var ReadRestController
     */
    private $readRestController;

    protected function setUp()
    {
        $this->entity = new Entity(
            new UUID(),
            new StringLiteral('labelName'),
            Visibility::INVISIBLE(),
            Privacy::PRIVACY_PRIVATE()
        );

        $this->request = new Request([
            QueryFactory::QUERY => 'label',
            QueryFactory::START => 5,
            QueryFactory::LIMIT => 2
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

        $this->userIdentification = $this->createMock(UserIdentificationInterface::class);
        $this->mockIsGodUser();
        $this->mockGetId();

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
            (string) $this->entity->getUuid()
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

        $expectedJsonResponse = new JsonResponse(new PagedCollection(
            (int)(5/2),
            2,
            [
                $this->entityToArray($this->entity),
                $this->entityToArray($this->entity)
            ],
            2
        ));

        $this->assertEquals($expectedJsonResponse, $jsonResponse);
    }

    private function mockGetByUuid()
    {
        $this->readService->method('getByUuid')
            ->with($this->entity->getUuid()->toNative())
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

    private function mockIsGodUser()
    {
        $this->userIdentification->method('isGodUser')
            ->willReturn(false);
    }

    private function mockGetId()
    {
        $this->userIdentification->method('getId')
            ->willReturn(new StringLiteral('userId'));
    }

    private function mockCreateQuery()
    {
        $this->queryFactory->method('createFromRequest')
            ->with($this->request)
            ->willReturn($this->query);
    }

    /**
     * @param Entity $entity
     * @return array
     */
    private function entityToArray(Entity $entity)
    {
        return [
            'uuid' => $entity->getUuid()->toNative(),
            'name' => $entity->getName()->toNative(),
            'visibility' => $entity->getVisibility()->toNative(),
            'privacy' => $entity->getPrivacy()->toNative()
        ];
    }
}
