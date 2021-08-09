<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\User;

use CultuurNet\UDB3\Http\Response\JsonLdResponse;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Jwt\Symfony\Authentication\JsonWebTokenFactory;
use CultuurNet\UDB3\User\UserIdentityDetails;
use CultuurNet\UDB3\User\UserIdentityResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Headers;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\EmailAddress;
use Zend\Diactoros\ServerRequest;

class UserIdentityControllerTest extends TestCase
{
    /**
     * @var UserIdentityController
     */
    private $userIdentityController;

    /**
     * @var UserIdentityResolver|MockObject
     */
    private $userIdentityResolver;

    protected function setUp(): void
    {
        $this->userIdentityResolver = $this->createMock(UserIdentityResolver::class);

        $this->userIdentityController = new UserIdentityController(
            $this->userIdentityResolver,
            JsonWebTokenFactory::createWithClaims(['uid' => 'current_user_id'])
        );
    }

    /**
     * @test
     */
    public function it_can_get_user_identity_by_email(): void
    {
        $userIdentity = new UserIdentityDetails(
            'user_id',
            'jane_doe',
            'jane.doe@anonymous.com'
        );

        $this->userIdentityResolver->expects($this->once())
            ->method('getUserByEmail')
            ->willReturn($userIdentity);

        $expected = [
            'uuid' => 'user_id',
            'email' => 'jane.doe@anonymous.com',
            'username' => 'jane_doe',
        ];

        $response = $this->userIdentityController->getByEmailAddress(
            (new ServerRequest())->withAttribute('emailAddress', 'jane.doe@anonymous.com')
        );

        $this->assertJsonResponse(new JsonLdResponse($expected), $response);
    }

    /**
     * @test
     */
    public function it_returns_not_found_on_get_by_email_when_email_is_missing(): void
    {
        $response = $this->userIdentityController->getByEmailAddress(
            (new ServerRequest())
                ->withAttribute('emailAddress', 'foo')
        );

        $this->assertJsonResponse(
            new JsonResponse(
                [
                    'type' => 'https://api.publiq.be/probs/uitdatabank/invalid-email-address',
                    'title' => 'Invalid email address',
                    'status' => 400,
                    'detail' => '"foo" is not a valid email address',
                ],
                400,
                new Headers(
                    [
                        'Content-Type' => [
                            'application/problem+json',
                        ],
                    ]
                )
            ),
            $response
        );
    }

    /**
     * @test
     */
    public function it_returns_not_found_on_get_by_email_when_user_identity_not_found(): void
    {
        $this->userIdentityResolver->expects($this->once())
            ->method('getUserByEmail')
            ->with(new EmailAddress('jane.doe@anonymous.com'))
            ->willReturn(null);

        $response = $this->userIdentityController->getByEmailAddress(
            (new ServerRequest())->withAttribute('emailAddress', 'jane.doe@anonymous.com')
        );

        $this->assertJsonResponse(
            new JsonResponse(
                [
                    'type' => 'https://api.publiq.be/probs/uitdatabank/user-not-found',
                    'title' => 'User not found',
                    'status' => 404,
                    'detail' => 'No user found for the given email address.',
                ],
                404,
                new Headers(
                    [
                        'Content-Type' => [
                            'application/problem+json',
                        ],
                    ]
                )
            ),
            $response
        );
    }

    /**
     * @test
     */
    public function it_can_get_user_identity_of_current_user_from_token_that_contains_it(): void
    {
        $jwt = JsonWebTokenFactory::createWithClaims(
            [
                'uid' => 'current_user_id',
                'nick' => 'jane_doe',
                'email' => 'jane.doe@anonymous.com',
            ]
        );

        $this->userIdentityResolver->expects($this->never())
            ->method('getUserById');

        $controller = new UserIdentityController(
            $this->userIdentityResolver,
            $jwt
        );
        $response = $controller->getCurrentUser();

        $expected = [
            'uuid' => 'current_user_id',
            'email' => 'jane.doe@anonymous.com',
            'username' => 'jane_doe',
            'id' => 'current_user_id',
            'nick' => 'jane_doe',
        ];

        $this->assertJsonResponse(
            new JsonLdResponse($expected, 200, new Headers(['Cache-Control' => 'private'])),
            $response
        );
    }

    /**
     * @test
     */
    public function it_can_get_user_identity_of_current_user_from_auth0_if_not_in_token(): void
    {
        $userIdentity = new UserIdentityDetails(
            'current_user_id',
            'jane_doe',
            'jane.doe@anonymous.com'
        );

        $this->userIdentityResolver->expects($this->once())
            ->method('getUserById')
            ->with(new StringLiteral('current_user_id'))
            ->willReturn($userIdentity);

        $response = $this->userIdentityController->getCurrentUser();

        $expected = [
            'uuid' => 'current_user_id',
            'email' => 'jane.doe@anonymous.com',
            'username' => 'jane_doe',
            'id' => 'current_user_id',
            'nick' => 'jane_doe',
        ];

        $this->assertJsonResponse(
            new JsonLdResponse($expected, 200, new Headers(['Cache-Control' => 'private'])),
            $response
        );
    }

    /**
     * @test
     */
    public function it_returns_token_type_not_supported_on_get_current_user_if_a_client_access_token_is_used(): void
    {
        $userIdentityControllerWithClientToken = new UserIdentityController(
            $this->userIdentityResolver,
            JsonWebTokenFactory::createWithClaims(
                [
                    'sub' => 'clientId@clients',
                    'azp' => 'clientId',
                    'gty' => 'client-credentials',
                ]
            )
        );

        $response = $userIdentityControllerWithClientToken->getCurrentUser();

        $this->assertJsonResponse(
            new JsonResponse(
                [
                    'type' => 'https://api.publiq.be/probs/auth/token-not-supported',
                    'title' => 'Token not supported',
                    'status' => 400,
                    'detail' => 'Client access tokens are not supported on this endpoint because a user is required to return user info.',
                ],
                400,
                new Headers(
                    [
                        'Content-Type' => [
                            'application/problem+json',
                        ],
                    ]
                )
            ),
            $response
        );
    }

    /**
     * @test
     */
    public function it_returns_not_found_on_get_current_user_when_user_identity_not_found(): void
    {
        $this->userIdentityResolver->expects($this->once())
            ->method('getUserById')
            ->with(new StringLiteral('current_user_id'))
            ->willReturn(null);

        $response = $this->userIdentityController->getCurrentUser();

        $this->assertJsonResponse(
            new JsonResponse(
                [
                    'type' => 'https://api.publiq.be/probs/auth/token-not-supported',
                    'title' => 'Token not supported',
                    'status' => 400,
                    'detail' => 'No user found for the given token.',
                ],
                400,
                new Headers(
                    [
                        'Content-Type' => [
                            'application/problem+json',
                        ],
                    ]
                )
            ),
            $response
        );
    }

    private function assertJsonResponse(ResponseInterface $expectedResponse, ResponseInterface $actualResponse): void
    {
        $this->assertEquals($expectedResponse->getStatusCode(), $actualResponse->getStatusCode());
        $this->assertEquals($expectedResponse->getHeaders(), $actualResponse->getHeaders());
        $this->assertEquals($expectedResponse->getBody()->getContents(), $actualResponse->getBody()->getContents());
    }
}
