<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\User;

use Auth0\SDK\Contract\API\ManagementInterface;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\StringLiteral;

final class Auth0UserIdentityResolver implements UserIdentityResolver
{
    private ManagementInterface $auth0;

    public function __construct(ManagementInterface $auth0)
    {
        $this->auth0 = $auth0;
    }

    /**
     * @throws \Exception
     */
    public function getUserById(StringLiteral $userId): ?UserIdentityDetails
    {
        return $this->fetchUser('user_id:"' . $userId . '" OR app_metadata.uitidv1id:"' . $userId . '"');
    }

    public function getUserByEmail(EmailAddress $email): ?UserIdentityDetails
    {
        return $this->fetchUser('email:"' . urlencode($email->toString()) . '"');
    }

    public function getUserByNick(StringLiteral $nick): ?UserIdentityDetails
    {
        return $this->fetchUser('email:"' . urlencode($nick->toNative()) . '" OR nickname:"' . urlencode($nick->toNative()) . '"');
    }

    private function fetchUser(string $query): ?UserIdentityDetails
    {
        $users = $this->auth0->users()->getAll(
            ['q' => $query]
        );

        return $this->normalizeResult($users);
    }

    private function normalizeResult(array $users): ?UserIdentityDetails
    {
        if (empty($users)) {
            return null;
        }

        $user = array_shift($users);

        return new UserIdentityDetails(
            $this->extractUserId($user),
            $user['nickname'],
            $user['email']
        );
    }

    private function extractUserId(array $user): string
    {
        if (isset($user['app_metadata']['uitidv1id'])) {
            return $user['app_metadata']['uitidv1id'];
        }
        return $user['user_id'];
    }
}
