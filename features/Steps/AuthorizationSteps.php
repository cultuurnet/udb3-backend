<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Steps;

use CultuurNet\UDB3\Json;

trait AuthorizationSteps
{
    /**
     * @Given I am using the UDB3 base URL
     */
    public function iAmUsingTheUDB3BaseURL(): void
    {
        $this->variableState->setVariable('baseUrl', $this->config['base_url']);
        $this->variableState->setVariable('baseUrlSapi3', $this->config['base_url_sapi3']);

        $this->requestState->setBaseUrl($this->config['base_url']);
    }

    /**
     * @Given I am using the Search API v3 base URL
     */
    public function iAmUsingTheSearchApiVBaseUrl(): void
    {
        $this->variableState->setVariable('baseUrl', $this->config['base_url_sapi3']);
        $this->requestState->setBaseUrl($this->config['base_url_sapi3']);
    }

    /**
     * @Given I am using the RDF base URL
     */
    public function iAmUsingTheRDFBaseURL(): void
    {
        $this->variableState->setVariable('baseUrl', $this->config['base_url_rdf']);
        $this->requestState->setBaseUrl($this->config['base_url_rdf']);
    }

    /**
     * @Given I am using the UiTiD base URL
     */
    public function iAmUsingTheUiTiDBaseURL(): void
    {
        $this->variableState->setVariable('baseUrl', $this->config['base_url_uitid']);
        $this->requestState->setBaseUrl($this->config['base_url_uitid']);
    }

    /**
     * @Given I am using an UiTID v1 API key of consumer :consumerName
     */
    public function iAmUsingAnUitidV1ApiKeyOfConsumer(string $consumerName): void
    {
        $this->requestState->setApiKey($this->config['apiKeys'][$consumerName]);
    }

    /**
     * @Given I am authorized as JWT provider v1 user :userName
     */
    public function iAmAuthorizedAsJwtProviderV1User(string $userName): void
    {
        $this->requestState->setJwt($this->config['users']['uitid_v1'][$userName]['jwt']);
    }

    /**
     * @Given I am authorized as JWT provider v2 user :userName
     */
    public function iAmAuthorizedAsJwtProviderV2User(string $userName): void
    {
        $this->iAmUsingTheUiTiDBaseURL();

        $response = $this->getHttpClient()->postJSON(
            '/oauth/token',
            Json::encode([
                'username' => $this->config['users']['uitid_v2'][$userName]['username'],
                'password' => $this->config['users']['uitid_v2'][$userName]['password'],
                'client_id' => $this->config['clients']['jwt_provider_v2']['client_id'],
                'client_secret' => $this->config['clients']['jwt_provider_v2']['client_secret'],
                'grant_type' => 'password',
                'audience' => 'https://api.publiq.be',
                'scope' => 'openid profile email',
            ])
        );
        $this->responseState->setResponse($response);

        $accessToken = $this->responseState->getJsonContent()['id_token'];
        $this->requestState->setJwt($accessToken);

        $this->iAmUsingTheUDB3BaseURL();
    }

    /**
     * @Given I am authorized with an OAuth client access token for :clientName
     */
    public function iAmAuthorizedWithAnOAuthClientAccessTokenFor(string $clientName): void
    {
        $this->iAmUsingTheUiTiDBaseURL();

        $response = $this->getHttpClient()->postJSON(
            '/oauth/token',
            Json::encode([
                'client_id' => $this->config['clients'][$clientName]['client_id'],
                'client_secret' => $this->config['clients'][$clientName]['client_secret'],
                'grant_type' => 'client_credentials',
                'audience' => 'https://api.publiq.be',
            ])
        );
        $this->responseState->setResponse($response);

        $accessToken = $this->responseState->getJsonContent()['access_token'];
        $this->requestState->setJwt($accessToken);

        $this->iAmUsingTheUDB3BaseURL();
    }

    /**
     * @Given I am authorized with an OAuth user access token for :userName via client :clientName
     */
    public function iAmAuthorizedWithAn0AuthUserAccessTokenForViaClient(string $userName, string $clientName): void
    {
        $this->iAmUsingTheUiTiDBaseURL();

        $response = $this->getHttpClient()->postJSON(
            '/oauth/token',
            Json::encode([
                'username' => $this->config['users']['uitid_v2'][$userName]['username'],
                'password' => $this->config['users']['uitid_v2'][$userName]['password'],
                'client_id' => $this->config['clients'][$clientName]['client_id'],
                'client_secret' => $this->config['clients'][$clientName]['client_secret'],
                'grant_type' => 'password',
                'audience' => 'https://api.publiq.be',
                'scope' => 'profile email',
            ])
        );
        $this->responseState->setResponse($response);

        $accessToken = $this->responseState->getJsonContent()['access_token'];
        $this->requestState->setJwt($accessToken);

        $this->iAmUsingTheUDB3BaseURL();
    }

    /**
     * @Given I am not authorized
     */
    public function iAmNotAuthorized(): void
    {
        $this->requestState->setJwt('');
    }

    /**
     * @Given I am not using an UiTID v1 API key
     */
    public function iAmNotUsingAnUitidV1ApiKey(): void
    {
        $this->requestState->setApiKey('');
    }

    /**
     * @Given I am using a x-client-id header for client :clientId
     */
    public function iAmUsingAXClientIdHeaderForClient(string $clientId): void
    {
        $this->requestState->setClientId($this->config['clients'][$clientId]['client_id']);
    }

    /**
     * @Given I am not using a x-client-id header
     */
    public function iAmNotUsingAXClientIdHeader(): void
    {
        $this->requestState->setClientId('');
    }
}
