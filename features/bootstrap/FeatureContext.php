<?php

declare(strict_types=1);

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Testwork\Hook\Scope\BeforeSuiteScope;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\State\RequestState;
use CultuurNet\UDB3\State\ResponseState;
use CultuurNet\UDB3\State\VariableState;
use CultuurNet\UDB3\Steps\AuthorizationSteps;
use CultuurNet\UDB3\Steps\CuratorSteps;
use CultuurNet\UDB3\Steps\EventSteps;
use CultuurNet\UDB3\Steps\LabelSteps;
use CultuurNet\UDB3\Steps\MailSteps;
use CultuurNet\UDB3\Steps\OrganizerSteps;
use CultuurNet\UDB3\Steps\OwnershipSteps;
use CultuurNet\UDB3\Steps\PlaceSteps;
use CultuurNet\UDB3\Steps\RequestSteps;
use CultuurNet\UDB3\Steps\ResponseSteps;
use CultuurNet\UDB3\Steps\RoleSteps;
use CultuurNet\UDB3\Steps\UtilitySteps;
use CultuurNet\UDB3\Support\Fixtures;
use CultuurNet\UDB3\Support\HttpClient;
use CultuurNet\UDB3\Support\MailClient;
use CultuurNet\UDB3\Support\MailPitClient;
use CultuurNet\UDB3\Support\TokenCache;

final class FeatureContext implements Context
{
    use AuthorizationSteps;
    use RequestSteps;
    use ResponseSteps;
    use UtilitySteps;

    use CuratorSteps;
    use EventSteps;
    use OrganizerSteps;
    use OwnershipSteps;
    use PlaceSteps;
    use LabelSteps;
    use RoleSteps;
    use MailSteps;

    private array $config;
    private Fixtures $fixtures;

    private VariableState $variableState;
    private RequestState $requestState;
    private ResponseState $responseState;

    public function __construct()
    {
        $this->config = require __DIR__ . '/../config.features.php';

        $this->fixtures = new Fixtures();

        $this->requestState = new RequestState();
        $this->variableState = new VariableState();
        $this->responseState = new ResponseState();
    }

    private function getHttpClient(): HttpClient
    {
        return new HttpClient(
            $this->requestState->getJwt(),
            $this->requestState->getApiKey(),
            $this->requestState->getClientId(),
            $this->requestState->getContentTypeHeader(),
            $this->requestState->getAcceptHeader(),
            $this->requestState->getBaseUrl(),
            $this->requestState->getUrlParams()
        );
    }

    private function getMailClient(): MailClient
    {
        return new MailPitClient($this->config['base_url_mailpit']);
    }

    /**
     * @BeforeSuite
     */
    public static function beforeSuite(BeforeSuiteScope $scope): void
    {
        TokenCache::clearTokens();
    }

    /**
     * @BeforeScenario @mails
     */
    public function beforeScenarioMails(BeforeScenarioScope $scope): void
    {
        $this->getMailClient()->deleteAllMails();
    }

    /**
     * @BeforeScenario @testIsolation
     */
    public function beforeScenarioTestIsolation(BeforeScenarioScope $scope): void
    {
        VariableState::setScenarioLabel('scenario-' . Uuid::uuid4()->toString());
    }

    /**
     * @AfterScenario @testIsolation
     */
    public function afterScenarioTestIsolation(AfterScenarioScope $scope): void
    {
        VariableState::clearScenarioLabel();
    }

    /**
     * Resolves %{variables} in every step argument that holds one, so no step has to do it itself.
     * Arguments without a variable are left alone, which keeps the ones that take a variable name
     * rather than a value, like "I set the variable :variableName to :value", intact.
     *
     * @Transform /^(.*%\{.+)$/
     */
    public function replaceVariablesInArgument(string $argument): string
    {
        return $this->variableState->replaceVariables($argument);
    }

    /**
     * Same for the multiline arguments, the request payloads and expected response bodies.
     *
     * @Transform
     */
    public function replaceVariablesInMultilineArgument(PyStringNode $argument): PyStringNode
    {
        return new PyStringNode(
            explode("\n", $this->variableState->replaceVariables($argument->getRaw())),
            $argument->getLine()
        );
    }
}
