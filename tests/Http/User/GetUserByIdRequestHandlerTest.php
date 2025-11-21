<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\User;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\JsonLdResponse;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\User\UserIdentityDetails;
use CultuurNet\UDB3\User\UserIdentityResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class GetUserByIdRequestHandlerTest extends TestCase
{
    use AssertJsonResponseTrait;
    use AssertApiProblemTrait;

    private GetUserByIdRequestHandler $getUserByIdRequestHandler;

    private UserIdentityResolver&MockObject $userIdentityResolver;

    protected function setUp(): void
    {
        $this->userIdentityResolver = $this->createMock(UserIdentityResolver::class);

        $this->getUserByIdRequestHandler = new GetUserByIdRequestHandler(
            $this->userIdentityResolver
        );
    }

    /**
     * @test
     */
    public function it_can_get_user_identity_by_id(): void
    {
        $userIdentity = new UserIdentityDetails(
            'user_id',
            'jane_doe',
            'jane.doe@anonymous.com'
        );

        $this->userIdentityResolver->expects($this->once())
            ->method('getUserById')
            ->willReturn($userIdentity);

        $expected = [
            'uuid' => 'user_id',
            'email' => 'jane.doe@anonymous.com',
            'username' => 'jane_doe',
        ];

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('userId', 'user_id')
            ->build('GET');

        $response = $this->getUserByIdRequestHandler->handle($request);

        $this->assertJsonResponse(new JsonLdResponse($expected), $response);
    }

    /**
     * @test
     */
    public function it_throws_when_user_identity_is_not_found(): void
    {
        $this->userIdentityResolver->expects($this->once())
            ->method('getUserById')
            ->willReturn(null);

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('userId', 'user_id')
            ->build('GET');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::urlNotFound('No user found for the given user ID.'),
            fn () => $this->getUserByIdRequestHandler->handle($request)
        );
    }
}
