<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\User;

use Crell\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Response\ApiProblemJsonResponse;
use CultuurNet\UDB3\Http\Response\JsonLdResponse;
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
            'current_user_id'
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

        $response = $this->userIdentityController->getByEmailAddress(
            (new ServerRequest())->withAttribute('emailAddress', 'jane.doe@anonymous.com')
        );

        $this->assertJsonResponse(new JsonLdResponse($this->userIdentityToArray($userIdentity)), $response);
    }

    /**
     * @test
     */
    public function it_returns_not_found_on_get_by_email_when_email_is_missing(): void
    {
        $response = $this->userIdentityController->getByEmailAddress(new ServerRequest());

        $this->assertJsonResponse($this->createUserNotFoundProblem(), $response);
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

        $this->assertJsonResponse($this->createUserNotFoundProblem(), $response);
    }

    /**
     * @test
     */
    public function it_can_get_user_identity_of_current_user(): void
    {
        $userIdentity = new UserIdentityDetails(
            new StringLiteral('current_user_id'),
            new StringLiteral('jane_doe'),
            new EmailAddress('jane.doe@anonymous.com')
        );

        $this->userIdentityResolver->expects($this->once())
            ->method('getUserById')
            ->with(new StringLiteral('current_user_id'))
            ->willReturn($userIdentity);

        $response = $this->userIdentityController->getCurrentUser();

        $userIdentityAsArray = $this->userIdentityToArray($userIdentity);
        $userIdentityAsArray['id'] = $userIdentity->getUserId()->toNative();
        $userIdentityAsArray['nick'] = $userIdentity->getUserName()->toNative();

        $this->assertJsonResponse(
            new JsonLdResponse($userIdentityAsArray, 200, ['Cache-Control' => 'private']),
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

        $this->assertJsonResponse($this->createUserNotFoundProblem(), $response);
    }

    private function createUserNotFoundProblem(): ApiProblemJsonResponse
    {
        return new ApiProblemJsonResponse(
            (new ApiProblem('User not found.'))->setStatus(404)
        );
    }

    private function assertJsonResponse(JsonResponse $expectedResponse, JsonResponse $actualResponse): void
    {
        $this->assertEquals($expectedResponse->getStatusCode(), $actualResponse->getStatusCode());
        $this->assertEquals($expectedResponse->getHeaders(), $actualResponse->getHeaders());
        $this->assertEquals($expectedResponse->getBody()->getContents(), $actualResponse->getBody()->getContents());
    }

    private function userIdentityToArray(UserIdentityDetails $userIdentityDetails): array
    {
        return [
            'uuid' => $userIdentityDetails->getUserId()->toNative(),
            'email' => $userIdentityDetails->getEmailAddress()->toNative(),
            'username' => $userIdentityDetails->getUserName()->toNative(),
        ];
    }
}
