<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\User;

use CultuurNet\UDB3\Http\Response\JsonLdResponse;
use CultuurNet\UDB3\Jwt\Symfony\Authentication\Token\Auth0ClientAccessToken;
use CultuurNet\UDB3\Jwt\Symfony\Authentication\Token\Auth0UserAccessToken;
use CultuurNet\UDB3\Jwt\Symfony\Authentication\Token\JwtProviderV1Token;
use CultuurNet\UDB3\Jwt\Symfony\Authentication\Token\MockTokenStringFactory;
use CultuurNet\UDB3\User\UserIdentityDetails;
use CultuurNet\UDB3\User\UserIdentityResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\EmailAddress;
use Zend\Diactoros\Response\JsonResponse;
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
            new JwtProviderV1Token(
                MockTokenStringFactory::createWithClaims(
                    [
                        'uid' => '982f6bd4-71b2-40fb-88f1-a7df699e76eb',
                        'nick' => 'foo',
                        'email' => 'mock@example.com',
                    ]
                )
            )
        );
    }

    /**
     * @test
     */
    public function it_can_get_user_identity_by_email(): void
    {
        $userIdentity = new UserIdentityDetails(
            new StringLiteral('user_id'),
            new StringLiteral('jane_doe'),
            new EmailAddress('jane.doe@anonymous.com')
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
                    'title' => 'Invalid email address',
                    'type' => 'https://api.publiq.be/probs/uitdatabank/invalid-email-address',
                    'status' => 400,
                    'detail' => '"foo" is not a valid email address',
                ],
                400,
                [
                    'Content-Type' => [
                        'application/problem+json',
                    ],
                ]
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
                    'title' => 'User not found',
                    'type' => 'https://api.publiq.be/probs/uitdatabank/user-not-found',
                    'status' => 404,
                    'detail' => 'No user found for the given email address.',
                ],
                404,
                [
                    'Content-Type' => [
                        'application/problem+json',
                    ],
                ]
            ),
            $response
        );
    }

    /**
     * @test
     */
    public function it_can_get_user_identity_of_current_user_from_token(): void
    {
        $this->userIdentityResolver->expects($this->never())
            ->method('getUserById');

        $response = $this->userIdentityController->getCurrentUser();

        $expected = [
            'uuid' => '982f6bd4-71b2-40fb-88f1-a7df699e76eb',
            'email' => 'mock@example.com',
            'username' => 'foo',
            'id' => '982f6bd4-71b2-40fb-88f1-a7df699e76eb',
            'nick' => 'foo',
        ];

        $this->assertJsonResponse(
            new JsonLdResponse($expected, 200, ['Cache-Control' => 'private']),
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
            new Auth0ClientAccessToken(
                MockTokenStringFactory::createWithClaims(
                    [
                        'sub' => 'mock-client-id@clients',
                        'azp' => 'mock-client-id',
                        'gty' => 'client-credentials',
                    ]
                )
            )
        );

        $response = $userIdentityControllerWithClientToken->getCurrentUser();

        $this->assertJsonResponse(
            new JsonResponse(
                [
                    'title' => 'Token not supported',
                    'type' => 'https://api.publiq.be/probs/auth/token-not-supported',
                    'status' => 400,
                    'detail' => 'Client access tokens are not supported on this endpoint because a user is required to return user info.',
                ],
                400,
                [
                    'Content-Type' => [
                        'application/problem+json',
                    ],
                ]
            ),
            $response
        );
    }

    /**
     * @test
     */
    public function it_returns_not_found_on_get_current_user_when_user_identity_not_found(): void
    {
        $token = new Auth0UserAccessToken(
            MockTokenStringFactory::createWithClaims(
                [
                    'sub' => 'auth0|c44dd39d-855d-4eaa-8b78-ee352fefcf3b',
                    'azp' => 'mock-client-id',
                ]
            ),
            $this->userIdentityResolver
        );

        $controller = new UserIdentityController(
            $this->userIdentityResolver,
            $token
        );

        $this->userIdentityResolver->expects($this->once())
            ->method('getUserById')
            ->with(new StringLiteral('auth0|c44dd39d-855d-4eaa-8b78-ee352fefcf3b'))
            ->willReturn(null);

        $response = $controller->getCurrentUser();

        $this->assertJsonResponse(
            new JsonResponse(
                [
                    'title' => 'Token not supported',
                    'type' => 'https://api.publiq.be/probs/auth/token-not-supported',
                    'status' => 400,
                    'detail' => 'No user found for the user id in the given token.',
                ],
                400,
                [
                    'Content-Type' => [
                        'application/problem+json',
                    ],
                ]
            ),
            $response
        );
    }

    private function assertJsonResponse(JsonResponse $expectedResponse, JsonResponse $actualResponse): void
    {
        $this->assertEquals($expectedResponse->getStatusCode(), $actualResponse->getStatusCode());
        $this->assertEquals($expectedResponse->getHeaders(), $actualResponse->getHeaders());
        $this->assertEquals($expectedResponse->getBody()->getContents(), $actualResponse->getBody()->getContents());
    }
}
