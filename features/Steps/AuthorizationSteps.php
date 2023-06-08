<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Steps;

trait AuthorizationSteps
{
    /**
     * @Given I am using the UDB3 base URL
     */
    public function iAmUsingTheUDB3BaseURL(): void
    {
        $this->variableState->addVariable('baseUrl', $this->config['base_url']);
        $this->requestState->setBaseUrl($this->config['base_url']);
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
     * @Given I am not authorized
     */
    public function iAmNotAuthorized(): void
    {
        $this->requestState->setJwt('');
    }
}
