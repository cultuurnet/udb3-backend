<?php

declare(strict_types=1);

use Behat\Behat\Context\Context;
use CultuurNet\UDB3\State\RequestState;
use CultuurNet\UDB3\Steps\RequestSteps;
use CultuurNet\UDB3\Steps\Response;
use CultuurNet\UDB3\Steps\UtilitySteps;
use CultuurNet\UDB3\Support\HttpClient;
use CultuurNet\UDB3\Support\Variables;
use CultuurNet\UDB3\Steps\AuthorizationSteps;
use CultuurNet\UDB3\Steps\Organizer;

final class FeatureContext implements Context
{
    use AuthorizationSteps;
    use RequestSteps;
    use Response;
    use UtilitySteps;

    use Organizer;

    private array $config;
    private Variables $variables;
    private RequestState $requestState;

    public function __construct()
    {
        $this->config = require __DIR__ . '/../config.php';

        $this->variables = new Variables();
        $this->requestState = new RequestState();
    }

    private function getHttpClient(): HttpClient
    {
        return new HttpClient(
            $this->requestState->getJwt(),
            $this->requestState->getApiKey(),
            $this->requestState->getContentTypeHeader(),
            $this->requestState->getAcceptHeader(),
            $this->requestState->getBaseUrl()
        );
    }
}
