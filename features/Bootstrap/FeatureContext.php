<?php

declare(strict_types=1);

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Testwork\Hook\Scope\BeforeSuiteScope;
use CultuurNet\UDB3\State\RequestState;
use CultuurNet\UDB3\State\ResponseState;
use CultuurNet\UDB3\State\VariableState;
use CultuurNet\UDB3\Steps\AuthorizationSteps;
use CultuurNet\UDB3\Steps\CuratorSteps;
use CultuurNet\UDB3\Steps\EventSteps;
use CultuurNet\UDB3\Steps\LabelSteps;
use CultuurNet\UDB3\Steps\OrganizerSteps;
use CultuurNet\UDB3\Steps\PlaceSteps;
use CultuurNet\UDB3\Steps\RequestSteps;
use CultuurNet\UDB3\Steps\ResponseSteps;
use CultuurNet\UDB3\Steps\RoleSteps;
use CultuurNet\UDB3\Steps\UtilitySteps;
use CultuurNet\UDB3\Support\Fixtures;
use CultuurNet\UDB3\Support\HttpClient;

final class FeatureContext implements Context
{
    use AuthorizationSteps;
    use RequestSteps;
    use ResponseSteps;
    use UtilitySteps;

    use CuratorSteps;
    use EventSteps;
    use OrganizerSteps;
    use PlaceSteps;
    use LabelSteps;
    use RoleSteps;

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

    /**
     * @BeforeSuite
     */
    public static function beforeSuite(BeforeSuiteScope $scope): void
    {
        // TODO: See if this can be approved
        $fc = new self();
        $fc->iAmUsingTheUDB3BaseURL();
        $fc->iAmUsingAnUitidV1ApiKeyOfConsumer('uitdatabank');
        $fc->iAmAuthorizedAsJwtProviderV1User('centraal_beheerder');

        // Create test labels if needed
        // Create "public-visible" if it doesn't exist yet and (re)set the right privacy and visibility in case its needed
        $fc->getLabel('public-visible');
        if ($fc->responseState->getStatusCode() === 404) {
            $fc->createLabel('public-visible', true, true);
        } else {
            $uuid = $fc->responseState->getJsonContent()['uuid'];
            $fc->iPatchTheLabelWithIdAndCommand($uuid, 'MakePublic');
            $fc->iPatchTheLabelWithIdAndCommand($uuid, 'MakeVisible');
        }

        // Create "public-invisible" if it doesn't exist yet and (re)set the right privacy and visibility in case its needed
        $fc->getLabel('public-invisible');
        if ($fc->responseState->getStatusCode() === 404) {
            $fc->createLabel('public-invisible', false, true);
        }
        $uuid = $fc->responseState->getJsonContent()['uuid'];
        $fc->iPatchTheLabelWithIdAndCommand($uuid, 'MakePublic');
        $fc->iPatchTheLabelWithIdAndCommand($uuid, 'MakeInvisible');

        // Create "private-visible" if it doesn't exist yet and (re)set the right privacy and visibility in case its needed
        $fc->getLabel('private-visible');
        if ($fc->responseState->getStatusCode() === 404) {
            $fc->createLabel('private-visible', true, false);
        }
        $uuid = $fc->responseState->getJsonContent()['uuid'];
        $fc->iPatchTheLabelWithIdAndCommand($uuid, 'MakePrivate');
        $fc->iPatchTheLabelWithIdAndCommand($uuid, 'MakeVisible');

        // Create "private-invisible" if it doesn't exist yet and (re)set the right privacy and visibility in case its needed
        $fc->getLabel('private-invisible');
        if ($fc->responseState->getStatusCode() === 404) {
            $fc->createLabel('private-invisible', false, false);
        }
        $uuid = $fc->responseState->getJsonContent()['uuid'];
        $fc->iPatchTheLabelWithIdAndCommand($uuid, 'MakePrivate');
        $fc->iPatchTheLabelWithIdAndCommand($uuid, 'MakeInvisible');

        // Create "special_label" if it doesn't exist yet
        $fc->getLabel('special_label');
        if ($fc->responseState->getStatusCode() === 404) {
            $fc->createLabel('special_label', true, true);
        }

        // Create "special-label" if it doesn't exist yet
        $fc->getLabel('special-label');
        if ($fc->responseState->getStatusCode() === 404) {
            $fc->createLabel('special-label', true, true);
        }

        // Create "special_label#" if it doesn't exist yet
        $fc->getLabel('special_label#');
        if ($fc->responseState->getStatusCode() === 404) {
            $fc->createLabel('special_label#', true, true);
        }

        // Create "special_label*" if it doesn't exist yet
        $fc->getLabel('special_label*');
        if ($fc->responseState->getStatusCode() === 404) {
            $fc->createLabel('special_label*', true, true);
        }

        // Create "private-diest" if it doesn't exist yet
        $fc->getLabel('private-diest');
        if ($fc->responseState->getStatusCode() === 404) {
            $fc->createLabel('private-diest', true, false);
        }
        $uuidLabelDiest = $fc->responseState->getJsonContent()['uuid'];
        $fc->iPatchTheLabelWithIdAndCommand($uuidLabelDiest, 'MakePrivate');

        // Create roles if needed
        // Reset roles on test users
        $fc->iSendAGetRequestTo('/users/emails/stan.vertessen+validatorDiest@cultuurnet.be');
        $uuidValidatorDiest = $fc->responseState->getJsonContent()['uuid'];
        $fc->variableState->setVariable(
            'uuid_validator_diest',
            $uuidValidatorDiest
        );
        $fc->iRemoveAllRolesForUserWithId($uuidValidatorDiest);

        $fc->iSendAGetRequestTo('/users/emails/stan.vertessen+validatorScherpenheuvel@cultuurnet.be');
        $uuidValidatorScherpenheuvel = $fc->responseState->getJsonContent()['uuid'];
        $fc->variableState->setVariable(
            'uuid_validator_scherpenheuvel',
            $uuidValidatorScherpenheuvel
        );
        $fc->iRemoveAllRolesForUserWithId($uuidValidatorScherpenheuvel);
        $fc->iSendAGetRequestTo('/users/emails/stan.vertessen+validatorPVB@cultuurnet.be');
        $uuidValidatorPvb = $fc->responseState->getJsonContent()['uuid'];
        $fc->variableState->setVariable(
            'uuid_validator_pvb',
            $uuidValidatorPvb
        );
        $fc->iRemoveAllRolesForUserWithId($uuidValidatorPvb);

        // Create role "Diest Validatoren"
        $fc->iSearchForARoleWithNameAndSaveTheIdAs('Diest validatoren');
        if (sizeof($fc->responseState->getJsonContent()['member']) > 0) {
            $uuidRoleDiest = $fc->responseState->getJsonContent()['member'][0]['uuid'];
        } else {
            $fc->createRole('Diest validatoren');
            $uuidRoleDiest = $fc->responseState->getJsonContent()['roleId'];
        }
        $fc->variableState->setVariable(
            'uuid_role_diest',
            $uuidRoleDiest
        );
        $fc->iSetTheJsonRequestPayloadTo(new PyStringNode(['{ "query": "(regions:nis-24020 OR labels:UiTinMijnRegio)" }'], 0));
        $fc->iSendAPostRequestTo('/roles/' . $uuidRoleDiest . '/constraints/');
        $fc->iSendAPutRequestTo('/roles/' . $uuidRoleDiest . '/permissions/AANBOD_BEWERKEN');
        $fc->iSendAPutRequestTo('/roles/' . $uuidRoleDiest . '/permissions/AANBOD_VERWIJDEREN');
        $fc->iSendAPutRequestTo('/roles/' . $uuidRoleDiest . '/permissions/AANBOD_MODEREREN');
        $fc->iSendAPutRequestTo('/roles/' . $uuidRoleDiest . '/users/' . $uuidValidatorDiest);
        $fc->iSendAPutRequestTo('/roles/' . $uuidRoleDiest . '/labels/' . $uuidLabelDiest);

        // Create role "Scherpenheuvel Validatoren"
        $fc->iSearchForARoleWithNameAndSaveTheIdAs('Scherpenheuvel validatoren');
        if (sizeof($fc->responseState->getJsonContent()['member']) > 0) {
            $uuidRoleScherpenheuvel = $fc->responseState->getJsonContent()['member'][0]['uuid'];
        } else {
            $fc->createRole('Scherpenheuvel validatoren');
            $uuidRoleScherpenheuvel = $fc->responseState->getJsonContent()['roleId'];
        }
        $fc->variableState->setVariable(
            'uuid_role_scherpenheuvel',
            $uuidRoleScherpenheuvel
        );
        $fc->iSetTheJsonRequestPayloadTo(new PyStringNode(['{"query": "regions:nis-24134"}'], 0));
        $fc->iSendAPostRequestTo('/roles/' . $uuidRoleScherpenheuvel . '/constraints/');
        $fc->iSendAPutRequestTo('/roles/' . $uuidRoleScherpenheuvel . '/permissions/AANBOD_BEWERKEN');
        $fc->iSendAPutRequestTo('/roles/' . $uuidRoleScherpenheuvel . '/permissions/AANBOD_VERWIJDEREN');
        $fc->iSendAPutRequestTo('/roles/' . $uuidRoleScherpenheuvel . '/permissions/AANBOD_MODEREREN');
        $fc->iSendAPutRequestTo('/roles/' . $uuidRoleScherpenheuvel . '/users/' . $uuidValidatorScherpenheuvel);

        // Create role "Vlaams-Brabant validatoren"
        $fc->iSearchForARoleWithNameAndSaveTheIdAs('Provincie Vlaams-Brabant validatoren');
        if (sizeof($fc->responseState->getJsonContent()['member']) > 0) {
            $uuidRolePvb = $fc->responseState->getJsonContent()['member'][0]['uuid'];
        } else {
            $fc->createRole('Provincie Vlaams-Brabant validatoren');
            $uuidRolePvb = $fc->responseState->getJsonContent()['roleId'];
        }
        $fc->variableState->setVariable(
            'uuid_role_pvb',
            $uuidRolePvb
        );
        $fc->iSetTheJsonRequestPayloadTo(new PyStringNode(['{ "query": "regions:nis-20001" }'], 0));
        $fc->iSendAPostRequestTo('/roles/' . $uuidRolePvb . '/constraints/');
        $fc->iSendAPutRequestTo('/roles/' . $uuidRolePvb . '/permissions/AANBOD_BEWERKEN');
        $fc->iSendAPutRequestTo('/roles/' . $uuidRolePvb . '/permissions/AANBOD_VERWIJDEREN');
        $fc->iSendAPutRequestTo('/roles/' . $uuidRolePvb . '/permissions/AANBOD_MODEREREN');
        $fc->iSendAPutRequestTo('/roles/' . $uuidRolePvb . '/users/' . $uuidValidatorPvb);
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
