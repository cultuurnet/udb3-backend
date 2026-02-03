<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\User\Keycloak;

use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\User\ClientIdResolver;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Client\ClientInterface;

final class KeycloakClientIdResolver implements ClientIdResolver
{
    public function __construct(
        readonly ClientInterface $client,
        readonly string $domain,
        readonly string $realm,
        readonly string $token
    ) {
    }

    public function hasEntryAccess(string $clientId): bool
    {
        $request = new Request(
            'GET',
            (new Uri($this->domain))
                ->withPath('/admin/realms/' . $this->realm . '/clients/')
                ->withQuery(http_build_query([
                    'clientId' => $clientId,
                ])),
            [
                'Authorization' => 'Bearer ' . $this->token,
            ]
        );

        $response = $this->client->sendRequest($request);

        if ($response->getStatusCode() !== 200) {
            $message = 'Keycloak error when getting metadata: ' . $response->getStatusCode();

            if ($response->getStatusCode() >= 500) {
                throw new ConnectException(
                    $message,
                    new Request('GET', '/admin/realms/' . $this->realm . '/clients/')
                );
            }
            return false;
        }

        $contents = Json::decodeAssociatively($response->getBody()->getContents());

        if (count($contents) !== 1) {
            return false;
        }

        if (!isset($contents[0]['defaultClientScopes'])) {
            return false;
        }

        return in_array('publiq-api-entry-scope', $contents[0]['defaultClientScopes']);
    }
}
