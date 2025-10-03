<?php

declare(strict_types=1);

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\AfterFeatureScope;
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

    private static function updateConfigFile(string $search, string $replace): void
    {
        $file = fopen('config.php', 'c+');

        if (flock($file, LOCK_EX)) {
            $configFile = stream_get_contents($file);
            $configFile = str_replace($search, $replace, $configFile);
            ftruncate($file, 0);
            rewind($file);
            fwrite($file, $configFile);
            fflush($file);
            flock($file, LOCK_UN);
        }

        fclose($file);
    }

    private static function disablePreventDuplicatePlaceCreation(): void
    {
        self::updateConfigFile(
            "'prevent_duplicate_places_creation' => true",
            "'prevent_duplicate_places_creation' => false"
        );
    }

    private static function enablePreventDuplicatePlaceCreation(): void
    {
        self::updateConfigFile(
            "'prevent_duplicate_places_creation' => false",
            "'prevent_duplicate_places_creation' => true"
        );
    }

    /**
     * @BeforeSuite
     */
    public static function beforeSuite(BeforeSuiteScope $scope): void
    {
        self::disablePreventDuplicatePlaceCreation();

        TokenCache::clearTokens();
    }

    /**
     * @BeforeFeature @duplicate
     */
    public static function beforeFeatureDuplicate(BeforeFeatureScope $scope): void
    {
        self::enablePreventDuplicatePlaceCreation();
    }

    /**
     * @AfterFeature @duplicate
     */
    public static function afterFeatureDuplicate(AfterFeatureScope $scope): void
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
