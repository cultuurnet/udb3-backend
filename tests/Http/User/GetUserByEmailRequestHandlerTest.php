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

final class GetUserByEmailRequestHandlerTest extends TestCase
{
    use AssertJsonResponseTrait;
    use AssertApiProblemTrait;

    private GetUserByEmailRequestHandler $getUserByEmailRequestHandler;

    /**
     * @var UserIdentityResolver&MockObject
     */
    private $userIdentityResolver;

    protected function setUp(): void
    {
        $this->userIdentityResolver = $this->createMock(UserIdentityResolver::class);

        $this->getUserByEmailRequestHandler = new GetUserByEmailRequestHandler(
            $this->userIdentityResolver
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

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('email', 'jane.doe@anonymous.com')
            ->build('GET');

        $response = $this->getUserByEmailRequestHandler->handle($request);

        $this->assertJsonResponse(new JsonLdResponse($expected), $response);
    }

    /**
     * @test
     */
    public function it_throws_on_invalid_email_address(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('email', 'foo')
            ->build('GET');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::urlNotFound('"foo" is not a valid email address'),
            fn () => $this->getUserByEmailRequestHandler->handle($request)
        );
    }

    /**
     * @test
     */
    public function it_throws_when_user_identity_is_not_found(): void
    {
        $this->userIdentityResolver->expects($this->once())
            ->method('getUserByEmail')
            ->with(new EmailAddress('jane.doe@anonymous.com'))
            ->willReturn(null);

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('email', 'jane.doe@anonymous.com')
            ->build('GET');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::urlNotFound('No user found for the given email address.'),
            fn () => $this->getUserByEmailRequestHandler->handle($request)
        );
    }
}
