<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Role;

use CultuurNet\UDB3\EntityServiceInterface;
use CultuurNet\UDB3\Event\ReadModel\DocumentGoneException;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\Role\ReadModel\Permissions\UserPermissionsReadRepositoryInterface;
use CultuurNet\UDB3\Role\ReadModel\Search\RepositoryInterface;
use CultuurNet\UDB3\Role\ReadModel\Search\Results;
use CultuurNet\UDB3\Role\Services\RoleReadingServiceInterface;
use CultuurNet\UDB3\Http\Assert\JsonEquals;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;
use CultuurNet\UDB3\Role\ValueObjects\Permission;

class ReadRoleRestControllerTest extends TestCase
{
    public const EXISTING_ID = 'existingId';
    public const NON_EXISTING_ID = 'nonExistingId';
    public const REMOVED_ID = 'removedId';

    /**
     * @var ReadRoleRestController
     */
    private $roleRestController;

    /**
     * @var RoleReadingServiceInterface|MockObject
     */
    private $roleService;

    /**
     * @var JsonDocument
     */
    private $jsonDocument;

    /**
     * @var RepositoryInterface|MockObject
     */
    private $roleSearchRepository;

    /**
     * @var JsonEquals
     */
    private $jsonEquals;

    /**
     * @var \CultureFeed_User
     */
    private $cfUser;

    /**
     * @var array
     */
    private $authorizationList;

    /**
     * @var UserPermissionsReadRepositoryInterface|MockObject
     */
    private $permissionsRepository;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        $this->jsonDocument = new JsonDocument('id', 'role');

        /** @var EntityServiceInterface|MockObject $entityServiceInterface */
        $entityServiceInterface = $this->createMock(EntityServiceInterface::class);

        $this->roleService = $this->createMock(RoleReadingServiceInterface::class);

        $this->permissionsRepository = $this->createMock(UserPermissionsReadRepositoryInterface::class);

        $entityServiceInterface->method('getEntity')
            ->willReturnCallback(
                function ($id) {
                    switch ($id) {
                        case self::EXISTING_ID:
                            return $this->jsonDocument->getRawBody();
                        case self::REMOVED_ID:
                            throw new DocumentGoneException();
                        default:
                            return '';
                    }
                }
            );

        $this->roleSearchRepository = $this->createMock(RepositoryInterface::class);

        $this->cfUser = new \CultureFeed_User();
        $this->authorizationList = [
            'allow_all' => [
                0 => '948cf2a5-65c5-470e-ab55-97ee4b05f576',
            ],
        ];

        $this->roleRestController = new ReadRoleRestController(
            $entityServiceInterface,
            $this->roleService,
            $this->cfUser,
            $this->authorizationList,
            $this->roleSearchRepository,
            $this->permissionsRepository
        );

        $this->jsonEquals = new JsonEquals($this);
    }

    /**
     * @test
     */
    public function it_returns_a_http_response_with_json_get_for_a_role()
    {
        $jsonResponse = $this->roleRestController->get(self::EXISTING_ID);

        $this->assertEquals(Response::HTTP_OK, $jsonResponse->getStatusCode());
        $this->assertEquals($this->jsonDocument->getRawBody(), $jsonResponse->getContent());
    }

    /**
     * @test
     */
    public function it_returns_a_http_response_with_error_NOT_FOUND_for_getting_a_non_existing_role()
    {
        $jsonResponse = $this->roleRestController->get(self::NON_EXISTING_ID);

        $this->assertEquals(Response::HTTP_NOT_FOUND, $jsonResponse->getStatusCode());
    }

    /**
     * @test
     */
    public function it_returns_labels()
    {
        $roleId = new UUID();

        $this->roleService
            ->expects($this->once())
            ->method('getLabelsByRoleUuid')
            ->with($roleId)
            ->willReturn(
                new JsonDocument(
                    $roleId,
                    json_encode([])
                )
            );

        $response = $this->roleRestController->getRoleLabels($roleId->toNative());

        $this->assertEquals($response->getContent(), '[]');
    }

    /**
     * @test
     */
    public function it_responds_with_an_array_of_users_for_a_given_role_id()
    {
        $roleId = new UUID('57791495-93D0-45CE-8D02-D716EC38972A');

        $readmodelJson = file_get_contents(__DIR__ . '/samples/role_users_readmodel.json');
        $expectedResponseJson = file_get_contents(__DIR__ . '/samples/role_users_response.json');

        $readmodelDocument = new JsonDocument(
            $roleId->toNative(),
            $readmodelJson
        );

        $this->roleService->expects($this->once())
            ->method('getUsersByRoleUuid')
            ->with($roleId)
            ->willReturn($readmodelDocument);

        $response = $this->roleRestController->getRoleUsers($roleId->toNative());
        $actualResponseJson = $response->getContent();

        $this->jsonEquals->assert($expectedResponseJson, $actualResponseJson);
    }

    /**
     * @test
     */
    public function it_responds_with_an_array_of_roles_for_a_given_user_id()
    {
        $userId = new StringLiteral('12345');

        $readmodelJson = file_get_contents(__DIR__ . '/samples/role_users_readmodel.json');
        $expectedResponseJson = file_get_contents(__DIR__ . '/samples/role_users_response.json');

        $readmodelDocument = new JsonDocument(
            $userId->toNative(),
            $readmodelJson
        );

        $this->roleService->expects($this->once())
            ->method('getRolesByUserId')
            ->with($userId)
            ->willReturn($readmodelDocument);

        $response = $this->roleRestController->getUserRoles($userId->toNative());
        $actualResponseJson = $response->getContent();

        $this->jsonEquals->assert($expectedResponseJson, $actualResponseJson);
    }

    /**
     * @test
     */
    public function it_responds_with_an_empty_array_if_no_roles_document_is_found_for_a_given_user_id()
    {
        $userId = new StringLiteral('12345');

        $this->roleService->expects($this->once())
            ->method('getRolesByUserId')
            ->with($userId)
            ->willReturn(null);

        $response = $this->roleRestController->getUserRoles($userId->toNative());
        $responseJson = $response->getContent();

        $this->jsonEquals->assert('[]', $responseJson);
    }

    /**
     * @test
     */
    public function it_responds_with_an_array_of_roles_for_the_current_user()
    {
        $userId = new StringLiteral('12345');
        $this->cfUser->id = $userId->toNative();

        $readmodelJson = file_get_contents(__DIR__ . '/samples/user_roles_readmodel.json');
        $expectedResponseJson = file_get_contents(__DIR__ . '/samples/user_roles_response.json');

        $readmodelDocument = new JsonDocument(
            $userId->toNative(),
            $readmodelJson
        );

        $this->roleService->expects($this->once())
            ->method('getRolesByUserId')
            ->with($userId)
            ->willReturn($readmodelDocument);

        $response = $this->roleRestController->getCurrentUserRoles();
        $actualResponseJson = $response->getContent();

        $this->jsonEquals->assert($expectedResponseJson, $actualResponseJson);
    }

    /**
     * @test
     */
    public function it_can_search()
    {
        $request = new Request();
        $results = new Results('10', [], 0);
        $expectedResults = json_encode((object) [
            'itemsPerPage' => '10',
            'member' => [],
            'totalItems' => 0,
        ]);

        $this->roleSearchRepository
            ->expects($this->once())
            ->method('search')
            ->willReturn($results);

        $actualResult = $this->roleRestController->search($request);
        $this->assertEquals($expectedResults, $actualResult->getContent());
    }

    /**
     * @test
     */
    public function it_returns_an_array_of_permissions_for_the_current_god_user()
    {
        $userId = new StringLiteral('948cf2a5-65c5-470e-ab55-97ee4b05f576');
        $this->cfUser->id = $userId->toNative();

        $response = $this->roleRestController->getUserPermissions();
        $responseJson = $response->getContent();

        $expectedResponseJson = json_encode([
            'AANBOD_BEWERKEN',
            'AANBOD_MODEREREN',
            'AANBOD_VERWIJDEREN',
            'ORGANISATIES_BEHEREN',
            'ORGANISATIES_BEWERKEN',
            'GEBRUIKERS_BEHEREN',
            'LABELS_BEHEREN',
            'MEDIA_UPLOADEN',
            'VOORZIENINGEN_BEWERKEN',
            'PRODUCTIES_AANMAKEN',
        ]);

        $this->jsonEquals->assert($expectedResponseJson, $responseJson);
    }

    /**
     * @test
     */
    public function it_returns_an_array_of_permissions_for_the_current_user()
    {
        $userId = new StringLiteral('948cf2a5-65c5-470e-ab55-97ee4b05f577');
        $this->cfUser->id = $userId->toNative();

        $permissions = [
            0 => Permission::getByName('AANBOD_MODEREREN'),
        ];

        $this->permissionsRepository
            ->expects($this->once())
            ->method('getPermissions')
            ->willReturn($permissions);

        $response = $this->roleRestController->getUserPermissions();
        $responseJson = $response->getContent();

        $expectedResponseJson = json_encode([
            'AANBOD_MODEREREN',
        ]);

        $this->jsonEquals->assert($expectedResponseJson, $responseJson);
    }
}
