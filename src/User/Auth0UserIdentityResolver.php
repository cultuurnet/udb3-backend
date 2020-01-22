<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\User;

use Auth0\SDK\API\Management;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\EmailAddress;

final class Auth0UserIdentityResolver implements UserIdentityResolverInterface
{
    /**
     * @var Management
     */
    private $auth0;

    public function __construct(Management $auth0)
    {
        $this->auth0 = $auth0;
    }

    /**
     * @param StringLiteral $userId
     * @return UserIdentityDetails|null
     * @throws \Exception
     */
    public function getUserById(StringLiteral $userId): ?UserIdentityDetails
    {
        $users = $this->auth0->users()->getAll(
            ['q' => 'user_id:"' . $userId . '" OR app_metadata.uitidv1id:"' . $userId . '"']
        );

        if (empty($users)) {
            return null;
        }
        
        $user = array_shift($users);

        return new UserIdentityDetails(
            new StringLiteral($user['user_id']),
            new StringLiteral($user['name']),
            new EmailAddress($user['email'])
        );
    }

    public function getUserByEmail(EmailAddress $email): ?UserIdentityDetails
    {
        // @TODO use https://auth0.com/docs/users/search/v3/get-users-by-email-endpoint
        // This could return multiple users. Check with Erwin that we link accounts with the same email address so they
        // are "unique"!
        // If so, just return the first one.
        // If not, we have a bigger problem because we assume e-mail addresses to be unique in multiple places.
        return null;
    }

    public function getUserByNick(StringLiteral $nick): ?UserIdentityDetails
    {
        // @TODO use https://auth0.com/docs/users/search/v3/get-users-endpoint
        // NOTE! Since we use email as the fallback for username if it's not found in the token claims, we should also
        // support email here. So do something similar to `email:"$nick" OR <username field>:$nick`
        // Not sure what the username field is currently, check with Erwin.
        return null;
    }
}
