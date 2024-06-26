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
    public function it_can_get_a_user_by_id_with_v1_fallback(): void
    {
        $userId = $this->userIdentityDetails->getUserId();

        $matcher = $this->exactly(2);
        $this->client->expects($matcher)
            ->method('sendRequest')
            ->willReturnCallback(function (RequestInterface $request) use ($userId, $matcher) {
                $this->assertEquals(
                    'GET',
                    $request->getMethod()
                );
                $this->assertEquals(
                    'Bearer token',
                    $request->getHeaderLine('Authorization')
                );

                if ($matcher->getInvocationCount() === 1) {
                    $this->assertEquals(
                        '/admin/realms/realm/users/' . $userId,
                        $request->getUri()->getPath()
                    );

                    return new Response(404);
                }

                $this->assertEquals(
                    '/admin/realms/realm/users',
                    $request->getUri()->getPath()
                );

                $this->assertEquals(
                    'q=uitidv1id%3A' . $userId,
                    $request->getUri()->getQuery()
                );

                return new Response(
                    200,
                    [],
                    Json::encode([$this->userIdentityDetailsToArray($this->userIdentityDetails)]),
                );
            });

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
        $this->client->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request) {
                return $request->getUri()->getPath() === '/admin/realms/realm/users' &&
                    $request->getMethod() === 'GET' &&
                    $request->getHeaderLine('Authorization') === 'Bearer token' &&
                    $request->getUri()->getQuery() === 'q=nickname%3Ajohndoe';
            }))
            ->willReturn(
                new Response(
                    200,
                    [],
                    Json::encode([$this->userIdentityDetailsToArray($this->userIdentityDetails)])
                )
            );

        $userIdentityDetails = $this->keycloackUserIdentityResolver->getUserByNick('johndoe');

        $this->assertEquals($this->userIdentityDetails, $userIdentityDetails);
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
