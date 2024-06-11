<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\User\Auth0;

use Auth0\SDK\Contract\API\Management\UsersInterface;
use Auth0\SDK\Contract\API\ManagementInterface;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class Auth0UserIdentityResolverTest extends TestCase
{
    /**
     * @test
     */
    public function it_get_user_by_id(): void
    {
        $userId = '9f3e9228-4eca-40ad-982f-4420bf4bbf09';
        $email = 'ivo@hdz.com';
        $name = 'Caca';

        $user = $this->aUser($userId, $email, $name);

        $client = $this->createMock(ManagementInterface::class);
        $users = $this->createMock(UsersInterface::class);

        $client->expects($this->any())
            ->method('users')
            ->willReturn($users);

        $users->expects($this->any())
            ->method('getAll')
            ->with(['q' => 'user_id:"9f3e9228-4eca-40ad-982f-4420bf4bbf09" OR app_metadata.uitidv1id:"9f3e9228-4eca-40ad-982f-4420bf4bbf09"'])
            ->willReturn(new Response(200, [], json_encode([$user])));

        $auth0UserIdentityResolver = new Auth0UserIdentityResolver($client);

        $result = $auth0UserIdentityResolver->getUserById($userId);
        $this->assertEquals($userId, $result->getUserId());
        $this->assertEquals($name, $result->getUserName());
        $this->assertEquals($email, $result->getEmailAddress());
    }

    /**
     * @test
     */
    public function it_returns_null_for_no_user_matching_id(): void
    {
        $userId = '9f3e9228-4eca-40ad-982f-4420bf4bbf09';

        $client = $this->createMock(ManagementInterface::class);
        $users = $this->createMock(UsersInterface::class);

        $client->expects($this->any())
            ->method('users')
            ->willReturn($users);

        $users->expects($this->any())
            ->method('getAll')
            ->with(['q' => 'user_id:"9f3e9228-4eca-40ad-982f-4420bf4bbf09" OR app_metadata.uitidv1id:"9f3e9228-4eca-40ad-982f-4420bf4bbf09"'])
            ->willReturn(new Response(200, [], json_encode([])));

        $auth0UserIdentityResolver = new Auth0UserIdentityResolver($client);

        $result = $auth0UserIdentityResolver->getUserById($userId);
        $this->assertNull($result);
    }

    /**
     * @test
     */
    public function it_gets_user_by_email(): void
    {
        $userId = '9f3e9228-4eca-40ad-982f-4420bf4bbf09';
        $email = 'ivo@hdz.com';
        $name = 'Caca';

        $user = $this->aUser($userId, $email, $name);

        $client = $this->createMock(ManagementInterface::class);
        $users = $this->createMock(UsersInterface::class);

        $client->expects($this->any())
            ->method('users')
            ->willReturn($users);

        $users->expects($this->atLeast(1))
            ->method('getAll')
            ->with(['q' => 'email:"ivo@hdz.com"'])
            ->willReturn(new Response(200, [], json_encode([$user])));

        $auth0UserIdentityResolver = new Auth0UserIdentityResolver($client);

        $result = $auth0UserIdentityResolver->getUserByEmail(new EmailAddress($email));

        $this->assertEquals($userId, $result->getUserId());
        $this->assertEquals($name, $result->getUserName());
        $this->assertEquals($email, $result->getEmailAddress());
    }

    /**
     * @test
     */
    public function it_returns_null_for_no_user_matching_email(): void
    {
        $email = 'ivo@hdz.com';

        $client = $this->createMock(ManagementInterface::class);
        $users = $this->createMock(UsersInterface::class);

        $client->expects($this->any())
            ->method('users')
            ->willReturn($users);

        $users->expects($this->atLeast(1))
            ->method('getAll')
            ->with(['q' => 'email:"ivo@hdz.com"'])
            ->willReturn(new Response(200, [], json_encode([])));

        $auth0UserIdentityResolver = new Auth0UserIdentityResolver($client);

        $result = $auth0UserIdentityResolver->getUserByEmail(new EmailAddress($email));
        $this->assertNull($result);
    }

    /**
     * @test
     */
    public function it_gets_user_by_nick_name(): void
    {
        $userId = '9f3e9228-4eca-40ad-982f-4420bf4bbf09';
        $email = 'ivo@hdz.com';
        $name = 'Caca';

        $user = $this->aUser($userId, $email, $name);

        $client = $this->createMock(ManagementInterface::class);
        $users = $this->createMock(UsersInterface::class);

        $client->expects($this->atLeast(1))
            ->method('users')
            ->willReturn($users);

        $users->expects($this->atLeast(1))
            ->method('getAll')
            ->with(['q' => 'email:"Caca" OR nickname:"Caca"'])
            ->willReturn(new Response(200, [], json_encode([$user])));

        $auth0UserIdentityResolver = new Auth0UserIdentityResolver($client);

        $result = $auth0UserIdentityResolver->getUserByNick($name);

        $this->assertEquals($userId, $result->getUserId());
        $this->assertEquals($name, $result->getUserName());
        $this->assertEquals($email, $result->getEmailAddress());
    }

    /**
     * @test
     */
    public function it_returns_null_for_no_user_matching_nick_name(): void
    {
        $name = 'Caca';

        $client = $this->createMock(ManagementInterface::class);
        $users = $this->createMock(UsersInterface::class);

        $client->expects($this->atLeast(1))
            ->method('users')
            ->willReturn($users);

        $users->expects($this->atLeast(1))
            ->method('getAll')
            ->with(['q' => 'email:"Caca" OR nickname:"Caca"'])
            ->willReturn(new Response(200, [], json_encode([])));

        $auth0UserIdentityResolver = new Auth0UserIdentityResolver($client);

        $result = $auth0UserIdentityResolver->getUserByNick($name);

        $this->assertNull($result);
    }

    /**
     * @test
     */
    public function it_returns_old_uuid_if_it_is_present(): void
    {
        $user = $this->aUser('auth0|9f3e9228-4eca-40ad-982f-4420bf4bbf09', 'ivo@hdz.com', 'Caca');
        // only migrated users will have this "old" uuid
        $oldUuid = '9f3e9228-4eca-40ad-982f-4420bf4bbf09';
        $user['app_metadata']['uitidv1id'] = $oldUuid;

        $client = $this->createMock(ManagementInterface::class);
        $users = $this->createMock(UsersInterface::class);

        $client->expects($this->atLeast(1))
            ->method('users')
            ->willReturn($users);

        $users->expects($this->atLeast(1))
            ->method('getAll')
            ->willReturn(new Response(200, [], json_encode([$user])));

        $auth0UserIdentityResolver = new Auth0UserIdentityResolver($client);

        $result = $auth0UserIdentityResolver->getUserByNick('Caca');

        $this->assertEquals($oldUuid, $result->getUserId());
    }

    private function aUser(string $userId, string $email, string $name): array
    {
        return [
            'user_id' => $userId,
            'email' => $email,
            'nickname' => $name,
        ];
    }
}
