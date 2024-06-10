<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\User\Keycloack;

use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\User\ManagementTokenGenerator;
use CultuurNet\UDB3\User\UserIdentityDetails;
use CultuurNet\UDB3\User\UserIdentityResolver;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

final class KeycloackUserIdentityResolver implements UserIdentityResolver
{
    private ClientInterface $client;
    private string $domain;
    private ManagementTokenGenerator $managementTokenGenerator;

    public function __construct(
        ClientInterface $client,
        string $domain,
        ManagementTokenGenerator $managementTokenGenerator
    ) {
        $this->client = $client;
        $this->domain = $domain;
        $this->managementTokenGenerator = $managementTokenGenerator;
    }

    public function getUserById(string $userId): ?UserIdentityDetails
    {
        $request = $this->createRequestWithQuery(
            [
                'id' => $userId,
            ]
        );

        $response = $this->client->sendRequest($request);

        return $this->extractUser($response);
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

        return $this->extractUser($response);
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
                ->withPath('/admin/realms/master/users')
                ->withQuery(http_build_query($query)),
            [
                'Authorization' => 'Bearer ' . $this->managementTokenGenerator->newToken()->getToken(),
            ]
        );
    }

    private function extractUser(ResponseInterface $response): UserIdentityDetails
    {
        $users = Json::decodeAssociatively($response->getBody()->getContents());

        $user = array_shift($users);

        return new UserIdentityDetails(
            $user['id'],
            $user['username'],
            $user['email'],
        );
    }
}