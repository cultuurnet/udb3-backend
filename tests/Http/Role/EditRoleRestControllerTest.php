<?php

namespace CultuurNet\UDB3\Http\Role;

use Broadway\CommandHandling\CommandBusInterface;
use CultuurNet\Deserializer\DeserializerInterface;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Entity;
use CultuurNet\UDB3\Label\Services\ReadServiceInterface;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Role\Commands\RenameRole;
use CultuurNet\UDB3\Role\Commands\UpdateRoleRequestDeserializer;
use CultuurNet\UDB3\Role\Services\RoleEditingServiceInterface;
use CultuurNet\UDB3\Role\ValueObjects\Query;
use CultuurNet\UDB3\Http\Deserializer\Role\QueryJSONDeserializer;
use CultuurNet\UDB3\Http\HttpFoundation\ApiProblemJsonResponse;
use CultuurNet\UDB3\ValueObject\SapiVersion;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

class EditRoleRestControllerTest extends TestCase
{
    /**
     * @var string
     */
    private $roleId;

    /**
     * @var string
     */
    private $labelId;

    /**
     * @var RoleEditingServiceInterface|MockObject
     */
    private $editService;

    /**
     * @var CommandBusInterface|MockObject
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
     * @var ReadServiceInterface|MockObject
     */
    private $labelService;

    /**
     * @var EditRoleRestController
     */
    private $controller;

    public function setUp()
    {
        $this->roleId = (new UUID())->toNative();
        $this->labelId = (new UUID())->toNative();

        $this->editService = $this->createMock(RoleEditingServiceInterface::class);
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->updateRoleRequestDeserializer = $this->createMock(UpdateRoleRequestDeserializer::class);
        $this->labelService = $this->createMock(ReadServiceInterface::class);
        $this->queryJsonDeserializer = $this->createMock(DeserializerInterface::class);

        $this->controller = new EditRoleRestController(
            $this->editService,
            $this->commandBus,
            $this->updateRoleRequestDeserializer,
            $this->labelService,
            $this->queryJsonDeserializer
        );
    }

    /**
     * @test
     */
    public function it_creates_a_role()
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
        $this->assertEquals(['roleId' => $roleId->toNative()], json_decode($response->getContent(), true));
    }

    /**
     * @test
     */
    public function it_updates_a_role()
    {
        $roleId = 'd01e0e24-4a8e-11e6-beb8-9e71128cae77';
        $request = $this->makeRequest('PATCH', 'samples/update_role.json');
        $request->headers->set('Content-Type', 'application/ld+json;domain-model=RenameRole');

        $renameRole = new RenameRole(
            new UUID($roleId),
            new StringLiteral('editRoleName')
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
    public function it_adds_a_constraint()
    {
        $roleId = 'd01e0e24-4a8e-11e6-beb8-9e71128cae77';
        $constraintQuery = new Query('city:3000');
        $sapiVersion = 'v2';

        $request = $this->makeRequest('POST', 'samples/add_constraint.json');

        $this->queryJsonDeserializer->expects($this->once())
            ->method('deserialize')
            ->with(new StringLiteral($request->getContent()))
            ->willReturn($constraintQuery);

        $this->editService->expects($this->once())
            ->method('addConstraint')
            ->with(
                new UUID($roleId),
                SapiVersion::fromNative($sapiVersion),
                $constraintQuery
            );

        $response = $this->controller->addConstraint($request, $roleId, $sapiVersion);

        $this->assertEquals(204, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_updates_a_constraint()
    {
        $roleId = 'd01e0e24-4a8e-11e6-beb8-9e71128cae77';
        $constraintQuery = new Query('city:3000');
        $sapiVersion = 'v2';

        $request = $this->makeRequest('PUT', 'samples/add_constraint.json');

        $this->queryJsonDeserializer->expects($this->once())
            ->method('deserialize')
            ->with(new StringLiteral($request->getContent()))
            ->willReturn($constraintQuery);

        $this->editService->expects($this->once())
            ->method('updateConstraint')
            ->with(
                new UUID($roleId),
                SapiVersion::fromNative($sapiVersion),
                $constraintQuery
            );

        $response = $this->controller->updateConstraint($request, $roleId, $sapiVersion);

        $this->assertEquals(204, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_removes_a_constraint()
    {
        $roleId = 'd01e0e24-4a8e-11e6-beb8-9e71128cae77';
        $sapiVersion = 'v2';

        $this->editService->expects($this->once())
            ->method('removeConstraint')
            ->with(new UUID($roleId), SapiVersion::fromNative($sapiVersion));

        $response = $this->controller->removeConstraint($roleId, $sapiVersion);

        $this->assertEquals(204, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_deletes_a_role()
    {
        $roleId = 'd01e0e24-4a8e-11e6-beb8-9e71128cae77';

        $this->editService->expects($this->once())
            ->method('delete')
            ->with($roleId);

        $response = $this->controller->delete($roleId);

        $this->assertEquals(204, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_no_roleId_is_given_to_delete()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Required field roleId is missing');
        $this->controller->delete('');
    }

    /**
     * @test
     */
    public function it_adds_a_label()
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
    public function it_adds_a_label_by_name()
    {
        $labelName = 'foo';

        $label = new Entity(
            new UUID($this->labelId),
            new StringLiteral($labelName),
            Visibility::VISIBLE(),
            Privacy::PRIVACY_PUBLIC()
        );

        $this->labelService->expects($this->once())
            ->method('getByName')
            ->with(new StringLiteral($labelName))
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
    public function it_returns_an_error_response_when_adding_an_unknown_label()
    {
        $labelName = 'foo';

        $this->labelService->expects($this->once())
            ->method('getByName')
            ->with(new StringLiteral($labelName))
            ->willReturn(null);

        $response = $this->controller->addLabel($this->roleId, $labelName);

        $this->assertInstanceOf(ApiProblemJsonResponse::class, $response);
    }

    /**
     * @test
     */
    public function it_removes_a_label()
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
    public function it_removes_a_label_by_name()
    {
        $labelName = 'foo';

        $label = new Entity(
            new UUID($this->labelId),
            new StringLiteral($labelName),
            Visibility::VISIBLE(),
            Privacy::PRIVACY_PUBLIC()
        );

        $this->labelService->expects($this->once())
            ->method('getByName')
            ->with(new StringLiteral($labelName))
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
    public function it_returns_an_error_response_when_removing_an_unknown_label()
    {
        $labelName = 'foo';

        $this->labelService->expects($this->once())
            ->method('getByName')
            ->with(new StringLiteral($labelName))
            ->willReturn(null);

        $response = $this->controller->removeLabel($this->roleId, $labelName);

        $this->assertInstanceOf(ApiProblemJsonResponse::class, $response);
    }

    public function makeRequest($method, $file_name)
    {
        $content = $this->getJson($file_name);
        $request = new Request([], [], [], [], [], [], $content);
        $request->setMethod($method);

        return $request;
    }

    private function getJson($fileName)
    {
        $json = file_get_contents(
            __DIR__ . '/' . $fileName
        );

        return $json;
    }
}
