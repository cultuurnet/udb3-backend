<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Organizer;

use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Security\Permission\PermissionVoter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class GetPermissionsForGivenUserRequestHandlerTest extends TestCase
{
    use AssertJsonResponseTrait;

    private const ORGANIZER_ID = 'd67e5cbc-c085-4ee0-a97b-c3795d480bd4';

    private const GIVEN_USER_ID = 'cd8d2005-e978-4f4c-9eb6-a0c0104fd8d0';

    private PermissionVoter&MockObject $voter;

    private GetPermissionsForGivenUserRequestHandler $getPermissionsForGivenUserRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    public function setUp(): void
    {
        $this->voter = $this->createMock(PermissionVoter::class);

        $this->getPermissionsForGivenUserRequestHandler = new GetPermissionsForGivenUserRequestHandler(
            $this->voter
        );

        $this->psr7RequestBuilder = new Psr7RequestBuilder();
    }

    /**
     * @test
     */
    public function it_returns_an_array_of_permissions_for_a_given_user(): void
    {
        $this->voter->method('isAllowed')->willReturn(true);

        $getPermissionsForGivenUserRequest = $this->psr7RequestBuilder
            ->withRouteParameter('organizerId', self::ORGANIZER_ID)
            ->withRouteParameter('userId', self::GIVEN_USER_ID)
            ->build('GET');

        $response = $this->getPermissionsForGivenUserRequestHandler->handle($getPermissionsForGivenUserRequest);

        $this->assertJsonResponse(
            new JsonResponse([
                'permissions' => [
                    'Organisaties bewerken',
                ],
            ]),
            $response
        );
    }
}
