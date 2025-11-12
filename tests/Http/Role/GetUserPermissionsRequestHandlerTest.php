<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Role;

use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Role\ReadModel\Permissions\UserPermissionsReadRepositoryInterface;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GetUserPermissionsRequestHandlerTest extends TestCase
{
    use AssertJsonResponseTrait;

    private UserPermissionsReadRepositoryInterface&MockObject $permissionsRepository;

    protected function setUp(): void
    {
        $this->permissionsRepository = $this->createMock(UserPermissionsReadRepositoryInterface::class);
    }

    /**
     * @test
     */
    public function it_returns_an_array_of_permissions_for_the_current_god_user(): void
    {
        $isGodUser = true;
        $userId = '32379b45-1fa9-4b71-986e-0e29c9e12def';

        $handler = new GetUserPermissionsRequestHandler(
            $this->permissionsRepository,
            $userId,
            $isGodUser
        );

        $request = (new Psr7RequestBuilder())
            ->build('GET');
        $response = $handler->handle($request);

        $expectedPermissions = [
            'AANBOD_BEWERKEN',
            'AANBOD_MODEREREN',
            'AANBOD_VERWIJDEREN',
            'AANBOD_HISTORIEK',
            'ORGANISATIES_BEHEREN',
            'ORGANISATIES_BEWERKEN',
            'GEBRUIKERS_BEHEREN',
            'LABELS_BEHEREN',
            'VOORZIENINGEN_BEWERKEN',
            'PRODUCTIES_AANMAKEN',
            'FILMS_AANMAKEN',
            'MEDIA_UPLOADEN',
        ];

        $this->assertJsonResponse(new JsonResponse(
            $expectedPermissions,
            200
        ), $response);
    }

    /**
     * @test
     */
    public function it_returns_an_array_of_permissions_for_the_current_user(): void
    {
        $isGodUser = false;
        $userId = '32379b45-1fa9-4b71-986e-0e29c9e12def';
        $permissions = [
            0 => Permission::aanbodModereren(),
        ];

        $this->permissionsRepository
            ->expects($this->once())
            ->method('getPermissions')
            ->with($userId)
            ->willReturn($permissions);

        $handler = new GetUserPermissionsRequestHandler(
            $this->permissionsRepository,
            $userId,
            $isGodUser
        );

        $request = (new Psr7RequestBuilder())
            ->build('GET');
        $response = $handler->handle($request);

        $expectedPermissions = [
            'AANBOD_MODEREREN',
            'MEDIA_UPLOADEN',
        ];

        $this->assertJsonResponse(new JsonResponse(
            $expectedPermissions,
            200
        ), $response);
    }
}
