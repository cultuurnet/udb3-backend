<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Steps;

trait Authorization
{
    private string $baseUrl = '';
    private string $apiKey = '';
    private string $jwt = '';

    /**
     * @Given I am using the UDB3 base URL
     */
    public function iAmUsingTheUDB3BaseURL(): void
    {
        $this->baseUrl = $this->config['base_url'];
    }

    /**
     * @Given I am using an UiTID v1 API key of consumer :arg1
     */
    public function iAmUsingAnUitidV1ApiKeyOfConsumer($arg1): void
    {
        $this->apiKey = $this->config['apiKeys'][$arg1];
    }

    /**
     * @Given I am authorized as JWT provider v1 user :arg1
     */
    public function iAmAuthorizedAsJwtProviderV1User($arg1): void
    {
        $this->jwt = $this->config['users']['uitid_v1'][$arg1]['jwt'];
    }
}
