<?php

declare(strict_types=1);

use Behat\Behat\Context\Context;
use CultuurNet\UDB3\Steps\Request;
use CultuurNet\UDB3\Steps\Response;
use CultuurNet\UDB3\Steps\Utils;
use CultuurNet\UDB3\Support\HttpClient;
use CultuurNet\UDB3\Support\Variables;
use CultuurNet\UDB3\Steps\Authorization;
use CultuurNet\UDB3\Steps\Headers;
use CultuurNet\UDB3\Steps\Organizer;

final class FeatureContext implements Context
{
    use Authorization;
    use Headers;
    use Request;
    use Response;
    use Utils;

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
