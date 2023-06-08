<?php

declare(strict_types=1);

use Behat\Behat\Context\Context;
use CultuurNet\UDB3\State\RequestState;
use CultuurNet\UDB3\State\ResponseState;
use CultuurNet\UDB3\Steps\RequestSteps;
use CultuurNet\UDB3\Steps\ResponseSteps;
use CultuurNet\UDB3\Steps\UtilitySteps;
use CultuurNet\UDB3\Support\Fixtures;
use CultuurNet\UDB3\Support\HttpClient;
use CultuurNet\UDB3\Support\Variables;
use CultuurNet\UDB3\Steps\AuthorizationSteps;
use CultuurNet\UDB3\Steps\OrganizerSteps;

final class FeatureContext implements Context
{
    use AuthorizationSteps;
    use RequestSteps;
    use ResponseSteps;
    use UtilitySteps;

    use OrganizerSteps;

    private array $config;
    private Variables $variables;
    private Fixtures $fixtures;

    private RequestState $requestState;
    private ResponseState $responseState;

    public function __construct()
    {
        $this->config = require __DIR__ . '/../config.php';

        $this->variables = new Variables();
        $this->fixtures = new Fixtures();

        $this->requestState = new RequestState();
        $this->responseState = new ResponseState();
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
