<?php

declare(strict_types=1);

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\AfterFeatureScope;
use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Behat\Hook\Scope\BeforeFeatureScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Testwork\Hook\Scope\BeforeSuiteScope;
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
            $this->requestState->getBaseUrl()
        );
    }

    private function getMailClient(): MailClient
    {
        return new MailPitClient($this->config['base_url_mailpit']);
    }

    private static function disablePreventDuplicatePlaceCreation(): void
    {
        $configFile = file_get_contents('config.php');

        $configFile = str_replace(
            "'prevent_duplicate_places_creation' => true",
            "'prevent_duplicate_places_creation' => false",
            $configFile
        );

        file_put_contents('config.php', $configFile);
    }

    private static function enablePreventDuplicatePlaceCreation(): void
    {
        $configFile = file_get_contents('config.php');

        $configFile = str_replace(
            "'prevent_duplicate_places_creation' => false",
            "'prevent_duplicate_places_creation' => true",
            $configFile
        );

        file_put_contents('config.php', $configFile);
    }

    /**
     * @BeforeSuite
     */
    public static function beforeSuite(BeforeSuiteScope $scope): void
    {
        TokenCache::clearTokens();
    }

    /**
     * @BeforeScenario @duplicate
     */
    public function beforeScenarioDuplicate(BeforeScenarioScope $scope): void
    {
        self::enablePreventDuplicatePlaceCreation();
    }

    /**
     * @AfterScenario @duplicate
     */
    public function afterScenarioDuplicate(AfterScenarioScope $scope): void
    {
        self::disablePreventDuplicatePlaceCreation();
    }

    /**
     * @BeforeScenario @mails
     */
    public function beforeScenarioMails(BeforeScenarioScope $scope): void
    {
        $this->getMailClient()->deleteAllMails();
    }

    /**
     * @Transform :url
     */
    public function replaceUrl(string $url): string
    {
        return $this->variableState->replaceVariables($url);
    }

    /**
     * @Transform :id
     */
    public function replaceId(string $id): string
    {
        return $this->variableState->replaceVariables($id);
    }
}
