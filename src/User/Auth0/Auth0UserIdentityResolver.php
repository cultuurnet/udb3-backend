<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\User\Auth0;

use Auth0\SDK\Contract\API\ManagementInterface;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\User\UserIdentityDetails;
use CultuurNet\UDB3\User\UserIdentityResolver;

final class Auth0UserIdentityResolver implements UserIdentityResolver
{
    private ManagementInterface $auth0;

    public function __construct(ManagementInterface $auth0)
    {
        $this->auth0 = $auth0;
    }

    public function getUserById(string $userId): ?UserIdentityDetails
    {
        return $this->fetchUser('user_id:"' . $userId . '" OR app_metadata.uitidv1id:"' . $userId . '"');
    }

    public function getUserByEmail(EmailAddress $email): ?UserIdentityDetails
    {
        return $this->fetchUser('email:"' . $email->toString() . '"');
    }

    public function getUserByNick(string $nick): ?UserIdentityDetails
    {
        return $this->fetchUser('email:"' . $nick . '" OR nickname:"' . $nick . '"');
    }

    private function fetchUser(string $query): ?UserIdentityDetails
    {
        $response = $this->auth0->users()->getAll(
            ['q' => $query]
        );
        $users = Json::decodeAssociatively($response->getBody()->getContents());
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
        return $user['app_metadata']['uitidv1id'] ?? $user['user_id'];
    }
}
