<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\User;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Auth\Jwt\JsonWebTokenFactory;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\JsonLdResponse;
use CultuurNet\UDB3\User\UserIdentityDetails;
use CultuurNet\UDB3\User\UserIdentityResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

final class GetCurrentUserRequestHandlerTest extends TestCase
{
    use AssertJsonResponseTrait;
    use AssertApiProblemTrait;

    private ServerRequestInterface $request;

    private GetCurrentUserRequestHandler $getCurrentUserIdentityController;

    /**
     * @var UserIdentityResolver&MockObject
     */
    private $userIdentityResolver;

    protected function setUp(): void
    {
        $this->userIdentityResolver = $this->createMock(UserIdentityResolver::class);

        $this->request = (new Psr7RequestBuilder())->build('GET');

        $this->getCurrentUserIdentityController = new GetCurrentUserRequestHandler(
            $this->userIdentityResolver,
            JsonWebTokenFactory::createWithClaims(['uid' => 'current_user_id'])
        );
    }

    /**
     * @test
     */
    public function it_can_get_user_identity_of_current_user_from_token(): void
    {
        $this->userIdentityResolver->expects($this->never())
            ->method('getUserById');

        $getCurrentUserRequestHandler = new GetCurrentUserRequestHandler(
            $this->userIdentityResolver,
            JsonWebTokenFactory::createWithClaims(
                [
                    'uid' => 'current_user_id',
                    'nick' => 'jane_doe',
                    'email' => 'jane.doe@anonymous.com',
                ]
            )
        );

        $response = $getCurrentUserRequestHandler->handle($this->request);

        $expected = [
            'uuid' => 'current_user_id',
            'email' => 'jane.doe@anonymous.com',
            'username' => 'jane_doe',
            'id' => 'current_user_id',
            'nick' => 'jane_doe',
        ];

        $this->assertJsonResponse(
            new JsonLdResponse($expected, 200),
            $response
        );
    }

    /**
     * @test
     */
    public function it_can_get_user_identity_of_current_user_from_oauth_if_not_in_token(): void
    {
        $userIdentity = new UserIdentityDetails(
            'current_user_id',
            'jane_doe',
            'jane.doe@anonymous.com'
        );

        $this->userIdentityResolver->expects($this->once())
            ->method('getUserById')
            ->with('current_user_id')
            ->willReturn($userIdentity);

        $response = $this->getCurrentUserIdentityController->handle($this->request);

        $expected = [
            'uuid' => 'current_user_id',
            'email' => 'jane.doe@anonymous.com',
            'username' => 'jane_doe',
            'id' => 'current_user_id',
            'nick' => 'jane_doe',
        ];

        $this->assertJsonResponse(
            new JsonLdResponse($expected, 200),
            $response
        );
    }

    /**
     * @test
     */
    public function it_returns_token_type_not_supported_if_a_client_access_token_is_used(): void
    {
        $getCurrentUserIdentityController = new GetCurrentUserRequestHandler(
            $this->userIdentityResolver,
            JsonWebTokenFactory::createWithClaims(
                [
                    'sub' => 'clientId@clients',
                    'azp' => 'clientId',
                    'gty' => 'client-credentials',
                ]
            )
        );

        $this->assertCallableThrowsApiProblem(
            ApiProblem::unauthorized(
                'Client access tokens are not supported on this endpoint because a user is required to return user info.'
            ),
            fn () => $getCurrentUserIdentityController->handle($this->request)
        );
    }

    /**
     * @test
     */
    public function it_returns_not_found_when_user_identity_is_not_found(): void
    {
        $this->userIdentityResolver->expects($this->once())
            ->method('getUserById')
            ->with('current_user_id')
            ->willReturn(null);

        $this->assertCallableThrowsApiProblem(
            ApiProblem::unauthorized('No user found for the given token.'),
            fn () => $this->getCurrentUserIdentityController->handle($this->request)
        );
    }
}
