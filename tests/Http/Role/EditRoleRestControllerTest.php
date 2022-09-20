<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Role;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Deserializer\DeserializerInterface;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Entity;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Role\Commands\RenameRole;
use CultuurNet\UDB3\Role\Commands\UpdateRoleRequestDeserializer;
use CultuurNet\UDB3\Role\Services\RoleEditingServiceInterface;
use CultuurNet\UDB3\Role\ValueObjects\Query;
use CultuurNet\UDB3\Http\Deserializer\Role\QueryJSONDeserializer;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use CultuurNet\UDB3\StringLiteral;

class EditRoleRestControllerTest extends TestCase
{
    private string $roleId;

    private string $labelId;

    /**
     * @var RoleEditingServiceInterface|MockObject
     */
    private $editService;

    /**
     * @var CommandBus|MockObject
     */
    private $commandBus;

    /**
     * @var UpdateRoleRequestDeserializer|MockObject
     */
    private $updateRoleRequestDeserializer;

    /**
     * @var QueryJSONDeserializer|MockObject
     */
    private $queryJsonDeserializer;

    /**
     * @var ReadRepositoryInterface|MockObject
     */
    private $labelRepository;

    private EditRoleRestController $controller;

    public function setUp(): void
    {
        $this->roleId = '5a359014-d022-48e4-98e2-173496e636fb';
        $this->labelId = 'b426ab4f-2371-427b-b27c-4b6b7b283c2a';

        $this->editService = $this->createMock(RoleEditingServiceInterface::class);
        $this->commandBus = $this->createMock(CommandBus::class);
        $this->updateRoleRequestDeserializer = $this->createMock(UpdateRoleRequestDeserializer::class);
        $this->labelRepository = $this->createMock(ReadRepositoryInterface::class);
        $this->queryJsonDeserializer = $this->createMock(DeserializerInterface::class);

        $this->controller = new EditRoleRestController(
            $this->editService,
            $this->commandBus,
            $this->updateRoleRequestDeserializer,
            $this->labelRepository,
            $this->queryJsonDeserializer
        );
    }

    /**
     * @test
     */
    public function it_creates_a_role(): void
    {
        $roleId = new UUID('d01e0e24-4a8e-11e6-beb8-9e71128cae77');
        $roleName = new StringLiteral('roleName');

        $request = $this->makeRequest('POST', 'samples/create_role.json');

        $this->editService->expects($this->once())
            ->method('create')
            ->with($roleName)
            ->willReturn($roleId);

        $response = $this->controller->create($request);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals(['roleId' => $roleId->toString()], json_decode($response->getContent(), true));
    }

    /**
     * @test
     */
    public function it_updates_a_role(): void
    {
        $roleId = 'd01e0e24-4a8e-11e6-beb8-9e71128cae77';
        $request = $this->makeRequest('PATCH', 'samples/update_role.json');
        $request->headers->set('Content-Type', 'application/ld+json;domain-model=RenameRole');

        $renameRole = new RenameRole(
            new UUID($roleId),
            'editRoleName'
        );

        $this->updateRoleRequestDeserializer->expects($this->once())
            ->method('deserialize')
            ->with($request, $roleId)
            ->willReturn($renameRole);

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($renameRole);

        $response = $this->controller->update($request, $roleId);

        $this->assertEquals(204, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_adds_a_constraint(): void
    {
        $roleId = 'd01e0e24-4a8e-11e6-beb8-9e71128cae77';
        $constraintQuery = new Query('city:3000');

        $request = $this->makeRequest('POST', 'samples/add_constraint.json');

        $this->queryJsonDeserializer->expects($this->once())
            ->method('deserialize')
            ->with(new StringLiteral($request->getContent()))
            ->willReturn($constraintQuery);

        $this->editService->expects($this->once())
            ->method('addConstraint')
            ->with(
                new UUID($roleId),
                $constraintQuery
            );

        $response = $this->controller->addConstraint($request, $roleId);

        $this->assertEquals(204, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_updates_a_constraint(): void
    {
        $roleId = 'd01e0e24-4a8e-11e6-beb8-9e71128cae77';
        $constraintQuery = new Query('city:3000');

        $request = $this->makeRequest('PUT', 'samples/add_constraint.json');

        $this->queryJsonDeserializer->expects($this->once())
            ->method('deserialize')
            ->with(new StringLiteral($request->getContent()))
            ->willReturn($constraintQuery);

        $this->editService->expects($this->once())
            ->method('updateConstraint')
            ->with(
                new UUID($roleId),
                $constraintQuery
            );

        $response = $this->controller->updateConstraint($request, $roleId);

        $this->assertEquals(204, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_removes_a_constraint(): void
    {
        $roleId = 'd01e0e24-4a8e-11e6-beb8-9e71128cae77';

        $this->editService->expects($this->once())
            ->method('removeConstraint')
            ->with(new UUID($roleId));

        $response = $this->controller->removeConstraint($roleId);

        $this->assertEquals(204, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_deletes_a_role(): void
    {
        $roleId = new UUID('d01e0e24-4a8e-11e6-beb8-9e71128cae77');

        $this->editService->expects($this->once())
            ->method('delete')
            ->with($roleId);

        $response = $this->controller->delete($roleId->toString());

        $this->assertEquals(204, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_no_roleId_is_given_to_delete(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Required field id is missing');
        $this->controller->delete('');
    }

    /**
     * @test
     */
    public function it_adds_a_label(): void
    {
        $this->editService->expects($this->once())
            ->method('addLabel')
            ->with(
                new UUID($this->roleId),
                new UUID($this->labelId)
            );

        $response = $this->controller->addLabel($this->roleId, $this->labelId);

        $this->assertEquals(204, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_adds_a_label_by_name(): void
    {
        $labelName = 'foo';

        $label = new Entity(
            new UUID($this->labelId),
            new StringLiteral($labelName),
            Visibility::VISIBLE(),
            Privacy::PRIVACY_PUBLIC()
        );

        $this->labelRepository->expects($this->once())
            ->method('getByName')
            ->with($labelName)
            ->willReturn($label);

        $this->editService->expects($this->once())
            ->method('addLabel')
            ->with(
                new UUID($this->roleId),
                new UUID($this->labelId)
            );

        $response = $this->controller->addLabel($this->roleId, $labelName);

        $this->assertEquals(204, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_throws_an_api_problem_exception_when_adding_an_unknown_label(): void
    {
        $labelName = 'foo';

        $this->labelRepository->expects($this->once())
            ->method('getByName')
            ->with($labelName)
            ->willReturn(null);

        $this->expectException(ApiProblem::class);

        $this->controller->addLabel($this->roleId, $labelName);
    }

    /**
     * @test
     */
    public function it_removes_a_label(): void
    {
        $this->editService->expects($this->once())
            ->method('removeLabel')
            ->with(
                new UUID($this->roleId),
                new UUID($this->labelId)
            );

        $response = $this->controller->removeLabel($this->roleId, $this->labelId);

        $this->assertEquals(204, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_removes_a_label_by_name(): void
    {
        $labelName = 'foo';

        $label = new Entity(
            new UUID($this->labelId),
            new StringLiteral($labelName),
            Visibility::VISIBLE(),
            Privacy::PRIVACY_PUBLIC()
        );

        $this->labelRepository->expects($this->once())
            ->method('getByName')
            ->with($labelName)
            ->willReturn($label);

        $this->editService->expects($this->once())
            ->method('removeLabel')
            ->with(
                new UUID($this->roleId),
                new UUID($this->labelId)
            );

        $response = $this->controller->removeLabel($this->roleId, $labelName);

        $this->assertEquals(204, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_throws_an_api_problem_exception_when_removing_an_unknown_label(): void
    {
        $labelName = 'foo';

        $this->labelRepository->expects($this->once())
            ->method('getByName')
            ->with($labelName)
            ->willReturn(null);

        $this->expectException(ApiProblem::class);

        $this->controller->removeLabel($this->roleId, $labelName);
    }

    public function makeRequest($method, $file_name)
    {
        $content = $this->getJson($file_name);
        $request = new Request([], [], [], [], [], [], $content);
        $request->setMethod($method);

        return $request;
    }

    private function getJson($fileName): string
    {
        return file_get_contents(__DIR__ . '/' . $fileName);
    }
}
