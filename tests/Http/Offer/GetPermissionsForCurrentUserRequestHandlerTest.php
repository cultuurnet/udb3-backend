<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\Permission\PermissionVoter;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Headers;

class GetPermissionsForCurrentUserRequestHandlerTest extends TestCase
{
    use AssertJsonResponseTrait;

    private const OFFER_ID = 'd67e5cbc-c085-4ee0-a97b-c3795d480bd4';

    /**
     * @var Permission[]
     */
    private array $permissions;

    /**
     * @var PermissionVoter|MockObject
     */
    private $voter;

    private GetPermissionsForCurrentUserRequestHandler $getPermissionsForCurrentUserRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    private Headers $headers;

    public function setUp(): void
    {
        $permissionsToCheck = [
            Permission::aanbodBewerken(),
            Permission::aanbodModereren(),
            Permission::aanbodVerwijderen(),
        ];
        $this->permissions = $permissionsToCheck;
        $this->voter = $this->createMock(PermissionVoter::class);

        $currentUserId = 'cd8d2005-e978-4f4c-9eb6-a0c0104fd8d0';

        $this->getPermissionsForCurrentUserRequestHandler = new GetPermissionsForCurrentUserRequestHandler(
            $this->permissions,
            $this->voter,
            $currentUserId
        );

        $this->psr7RequestBuilder = new Psr7RequestBuilder();

        $this->headers = new Headers();
        $this->headers->setHeader('Cache-Control', 'private');
    }

    /**
     * @test
     */
    public function it_returns_an_array_of_permissions_for_the_current_user(): void
    {
        $this->voter->method('isAllowed')->willReturn(true);

        $getPermissionsForCurrentUserRequest = $this->psr7RequestBuilder
            ->withRouteParameter('offerId', self::OFFER_ID)
            ->build('GET');

        $response = $this->getPermissionsForCurrentUserRequestHandler->handle($getPermissionsForCurrentUserRequest);

        $this->assertJsonResponse(
            new JsonResponse([
                'permissions' => [
                    'Aanbod bewerken',
                    'Aanbod modereren',
                    'Aanbod verwijderen',
                ],
            ], StatusCodeInterface::STATUS_OK, $this->headers),
            $response
        );
    }
}
