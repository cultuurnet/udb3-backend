<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\User\Keycloak;

use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\User\UserIdentityDetails;
use CultuurNet\UDB3\User\UserIdentityResolver;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

final class KeycloakUserIdentityResolver implements UserIdentityResolver
{
    private ClientInterface $client;
    private string $domain;
    private string $realm;
    private string $token;

    public function __construct(
        ClientInterface $client,
        string $domain,
        string $realm,
        string $token
    ) {
        $this->client = $client;
        $this->domain = $domain;
        $this->realm = $realm;
        $this->token = $token;
    }

    public function getUserById(string $userId): ?UserIdentityDetails
    {
        $request = new Request(
            'GET',
            (new Uri($this->domain))
                ->withPath('/admin/realms/' . $this->realm . '/users/' . $userId),
            [
                'Authorization' => 'Bearer ' . $this->token,
            ]
        );

        $response = $this->client->sendRequest($request);

        if ($response->getStatusCode() === 404) {
            return null;
        }

        return $this->extractUser($response, true);
    }

    public function getUserByEmail(EmailAddress $email): ?UserIdentityDetails
    {
        $request = $this->createRequestWithQuery(
            [
                'email' => $email->toString(),
                'exact' => 'true',
            ]
        );

        $response = $this->client->sendRequest($request);

        if ($response->getStatusCode() === 404) {
            return null;
        }

        return $this->extractUser($response, false);
    }

    public function getUserByNick(string $nick): ?UserIdentityDetails
    {
        throw new RuntimeException('Nickname is not yet supported in Keycloak.');
    }

    private function createRequestWithQuery(array $query): Request
    {
        return new Request(
            'GET',
            (new Uri($this->domain))
                ->withPath('/admin/realms/' . $this->realm . '/users')
                ->withQuery(http_build_query($query)),
            [
                'Authorization' => 'Bearer ' . $this->token,
            ]
        );
    }

    private function extractUser(ResponseInterface $response, bool $fromProfile): ?UserIdentityDetails
    {
        $users = Json::decodeAssociatively($response->getBody()->getContents());

        if (empty($users)) {
            return null;
        }

        $user = $fromProfile ? $users : array_shift($users);

        return new UserIdentityDetails(
            $user['attributes']['uitidv1id'][0] ?? $user['id'],
            $user['attributes']['nickname'][0] ?? $user['username'],
            $user['email'],
        );
    }
}
