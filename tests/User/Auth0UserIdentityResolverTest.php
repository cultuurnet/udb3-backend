<?php declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\User;

use Auth0\SDK\API\Management;
use Auth0\SDK\API\Management\Users;
use CultuurNet\UDB3\User\Auth0UserIdentityResolver;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;

class Auth0UserIdentityResolverTest extends TestCase
{
    /**
     * @test
     */
    public function it_get_user_by_id()
    {
        $userId = '9f3e9228-4eca-40ad-982f-4420bf4bbf09';
        $email = 'ivo@hdz.com';
        $name = 'Caca';

        $user = [
            'user_id' => $userId,
            'email' => $email,
            'name' => $name,
        ];

        $client = $this->createMock(Management::class);
        $users = $this->createMock(Users::class);

        $client->expects($this->any())
            ->method('users')
            ->willReturn($users);

        $users->expects($this->any())
            ->method('getAll')
            ->with(['q' => 'user_id:"9f3e9228-4eca-40ad-982f-4420bf4bbf09" OR app_metadata.uitidv1id:"9f3e9228-4eca-40ad-982f-4420bf4bbf09"'])
            ->willReturn([$user]);

        $auth0UserIdentityResolver = new Auth0UserIdentityResolver($client);

        $result = $auth0UserIdentityResolver->getUserById(new StringLiteral($userId));
        $this->assertEquals($userId, $result->getUserId());
        $this->assertEquals($name, $result->getUserName());
        $this->assertEquals($email, $result->getEmailAddress());
    }

    /**
     * @test
     */
    public function it_returns_null_for_no_user_matching_id()
    {
        $userId = '9f3e9228-4eca-40ad-982f-4420bf4bbf09';

        $client = $this->createMock(Management::class);
        $users = $this->createMock(Users::class);

        $client->expects($this->any())
            ->method('users')
            ->willReturn($users);

        $users->expects($this->any())
            ->method('getAll')
            ->with(['q' => 'user_id:"9f3e9228-4eca-40ad-982f-4420bf4bbf09" OR app_metadata.uitidv1id:"9f3e9228-4eca-40ad-982f-4420bf4bbf09"'])
            ->willReturn([]);

        $auth0UserIdentityResolver = new Auth0UserIdentityResolver($client);

        $result = $auth0UserIdentityResolver->getUserById(new StringLiteral($userId));
        $this->assertNull($result);
    }
}
