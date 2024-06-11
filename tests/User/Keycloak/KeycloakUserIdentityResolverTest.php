<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\User\Keycloak;

use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\User\UserIdentityDetails;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;

class KeycloakUserIdentityResolverTest extends TestCase
{
    /**
     * @var ClientInterface&MockObject
     */
    private $client;

    private UserIdentityDetails $userIdentityDetails;

    private KeycloakUserIdentityResolver $keycloackUserIdentityResolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = $this->createMock(ClientInterface::class);

        $this->userIdentityDetails = new UserIdentityDetails(
            '9f3e9228-4eca-40ad-982f-4420bf4bbf09',
            'John Doe',
            'john@anonymous.com'
        );

        $this->keycloackUserIdentityResolver = new KeycloakUserIdentityResolver(
            $this->client,
            'http://keycloak',
            'realm',
            'token'
        );
    }

    /**
     * @test
     */
    public function it_can_get_a_user_by_id(): void
    {
        $userId = $this->userIdentityDetails->getUserId();

        $this->client->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request) use ($userId) {
                return $request->getUri()->getPath() === '/admin/realms/realm/users/' . $userId &&
                    $request->getMethod() === 'GET' &&
                    $request->getHeaderLine('Authorization') === 'Bearer token';
            }))
            ->willReturn(
                new Response(
                    200,
                    [],
                    Json::encode($this->userIdentityDetailsToArray($this->userIdentityDetails))
                )
            );

        $userIdentityDetails = $this->keycloackUserIdentityResolver->getUserById($userId);

        $this->assertEquals($this->userIdentityDetails, $userIdentityDetails);
    }

    /**
     * @test
     */
    public function it_can_get_a_user_by_email(): void
    {
        $this->client->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request) {
                return $request->getUri()->getPath() === '/admin/realms/realm/users' &&
                    $request->getMethod() === 'GET' &&
                    $request->getHeaderLine('Authorization') === 'Bearer token' &&
                    $request->getUri()->getQuery() === 'email=john%40anonymous.com&exact=true';
            }))
            ->willReturn(
                new Response(
                    200,
                    [],
                    Json::encode([$this->userIdentityDetailsToArray($this->userIdentityDetails)])
                )
            );

        $userIdentityDetails = $this->keycloackUserIdentityResolver->getUserByEmail(
            new EmailAddress($this->userIdentityDetails->getEmailAddress())
        );

        $this->assertEquals($this->userIdentityDetails, $userIdentityDetails);
    }

    /**
     * @test
     */
    public function test_it_can_get_a_user_by_nick(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Nickname is not yet supported in Keycloak.');

        $this->keycloackUserIdentityResolver->getUserByNick('nick');
    }

    private function userIdentityDetailsToArray(UserIdentityDetails $userIdentityDetails): array
    {
        return [
            'id' => $userIdentityDetails->getUserId(),
            'email' => $userIdentityDetails->getEmailAddress(),
            'username' => $userIdentityDetails->getUserName(),
        ];
    }
}
