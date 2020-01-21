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

    public function getUserById(StringLiteral $userId): ?UserIdentityDetails
    {
        $user = $this->auth0->users()
            ->get($userId->toNative());

        var_dump($user);
        exit;

        return null;
    }

    public function getUserByEmail(EmailAddress $email): ?UserIdentityDetails
    {
        return null;
    }

    public function getUserByNick(StringLiteral $nick): ?UserIdentityDetails
    {
        return null;
    }
}
