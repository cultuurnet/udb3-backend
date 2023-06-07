<?php

declare(strict_types=1);

use Behat\Behat\Context\Context;
use CultuurNet\UDB3\Support\HttpClient;
use CultuurNet\UDB3\Support\Variables;
use CultuurNet\UDB3\Traits\Authorization;
use CultuurNet\UDB3\Traits\Headers;
use CultuurNet\UDB3\Traits\Organizer;

final class FeatureContext implements Context
{
    use Authorization;
    use Headers;

    use Organizer;

    private array $config;
    private Variables $variables;

    public function __construct()
    {
        $this->config = require __DIR__ . '/../config.php';

        $this->variables = new Variables();
    }

    private function getHttpClient(): HttpClient
    {
        return new HttpClient(
            $this->jwt,
            $this->apiKey,
            $this->contentTypeHeader,
            $this->acceptHeader,
            $this->baseUrl
        );
    }
}