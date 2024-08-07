<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Steps;

use Behat\Gherkin\Node\PyStringNode;
use CultuurNet\UDB3\Json;

trait RoleSteps
{
    /**
     * @When I search for a role with name :name
     */
    public function iSearchForARoleWithName(string $name): void
    {
        $response = $this->getHttpClient()->get(
            '/roles?query=' . $name,
        );
        $this->responseState->setResponse($response);

        $this->theResponseStatusShouldBe(200);
    }

    /**
     * @Given I create a role with a random name of :nrOfCharacters characters
     */
    public function iCreateARoleWithARandomNameOfCharacters(int $nrOfCharacters): void
    {
        $this->iCreateARandomNameOfCharacters($nrOfCharacters);
        $this->createRole(
            $this->variableState->getVariable('name'),
        );
    }

    /**
     * @Given I remove all roles for user with id :userId
     */
    public function iRemoveAllRolesForUserWithId(string $userId): void
    {
        $userId = $this->variableState->replaceVariables($userId);
        $this->getRolesForUser($userId);

        $roles = $this->responseState->getJsonContent();
        foreach ($roles as $role) {
            $this->deleteARoleForUser($role['uuid'], $userId);
        }
    }

    /**
     * @Given roles test data is available
     */
    public function rolesTestDataIsAvailable(): void
    {
        // Create role "Diest Validatoren"
        $this->iSearchForARoleWithName('Diest validatoren');
        if (count($this->responseState->getJsonContent()['member']) === 0) {
            $this->createRole('Diest validatoren');
            $uuidRoleDiest = $this->responseState->getJsonContent()['roleId'];
            $this->iSetTheJsonRequestPayloadTo(new PyStringNode(['{ "query": "(regions:nis-24020 OR labels:UiTinMijnRegio)" }'], 0));
            $this->iSendAPostRequestTo('/roles/' . $uuidRoleDiest . '/constraints/');
            $this->iSendAPutRequestTo('/roles/' . $uuidRoleDiest . '/permissions/AANBOD_BEWERKEN');
            $this->iSendAPutRequestTo('/roles/' . $uuidRoleDiest . '/permissions/AANBOD_VERWIJDEREN');
            $this->iSendAPutRequestTo('/roles/' . $uuidRoleDiest . '/permissions/AANBOD_MODEREREN');
            $this->iSendAGetRequestTo('/users/emails/stan.vertessen+validatorDiest@cultuurnet.be');
            $uuidValidatorDiest = $this->responseState->getJsonContent()['uuid'];
            $this->iSendAPutRequestTo('/roles/' . $uuidRoleDiest . '/users/' . $uuidValidatorDiest);
            $this->getLabel('private-diest');
            if ($this->responseState->getStatusCode() === 404) {
                $this->createLabel('private-diest', true, false);
                $uuidLabelDiest = $this->responseState->getJsonContent()['uuid'];
                $this->iPatchTheLabelWithIdAndCommand($uuidLabelDiest, 'MakePrivate');
                $this->iSendAPutRequestTo('/roles/' . $uuidRoleDiest . '/labels/' . $uuidLabelDiest);
            }
        }

        // Create role "Scherpenheuvel Validatoren"
        $this->iSearchForARoleWithName('Scherpenheuvel validatoren');
        if (count($this->responseState->getJsonContent()['member']) === 0) {
            $this->createRole('Scherpenheuvel validatoren');
            $uuidRoleScherpenheuvel = $this->responseState->getJsonContent()['roleId'];
            $this->iSetTheJsonRequestPayloadTo(new PyStringNode(['{"query": "regions:nis-24134"}'], 0));
            $this->iSendAPostRequestTo('/roles/' . $uuidRoleScherpenheuvel . '/constraints/');
            $this->iSendAPutRequestTo('/roles/' . $uuidRoleScherpenheuvel . '/permissions/AANBOD_BEWERKEN');
            $this->iSendAPutRequestTo('/roles/' . $uuidRoleScherpenheuvel . '/permissions/AANBOD_VERWIJDEREN');
            $this->iSendAPutRequestTo('/roles/' . $uuidRoleScherpenheuvel . '/permissions/AANBOD_MODEREREN');
            $this->iSendAGetRequestTo('/users/emails/stan.vertessen+validatorScherpenheuvel@cultuurnet.be');
            $uuidValidatorScherpenheuvel = $this->responseState->getJsonContent()['uuid'];
            $this->iSendAPutRequestTo('/roles/' . $uuidRoleScherpenheuvel . '/users/' . $uuidValidatorScherpenheuvel);
        }

        // Create role "Vlaams-Brabant validatoren"
        $this->iSearchForARoleWithName('Provincie Vlaams-Brabant validatoren');
        if (count($this->responseState->getJsonContent()['member']) === 0) {
            $this->createRole('Provincie Vlaams-Brabant validatoren');
            $uuidRolePvb = $this->responseState->getJsonContent()['roleId'];
            $this->iSetTheJsonRequestPayloadTo(new PyStringNode(['{ "query": "regions:nis-20001" }'], 0));
            $this->iSendAPostRequestTo('/roles/' . $uuidRolePvb . '/constraints/');
            $this->iSendAPutRequestTo('/roles/' . $uuidRolePvb . '/permissions/AANBOD_BEWERKEN');
            $this->iSendAPutRequestTo('/roles/' . $uuidRolePvb . '/permissions/AANBOD_VERWIJDEREN');
            $this->iSendAPutRequestTo('/roles/' . $uuidRolePvb . '/permissions/AANBOD_MODEREREN');
            $this->iSendAGetRequestTo('/users/emails/stan.vertessen+validatorPVB@cultuurnet.be');
            $uuidValidatorPvb = $this->responseState->getJsonContent()['uuid'];
            $this->iSendAPutRequestTo('/roles/' . $uuidRolePvb . '/users/' . $uuidValidatorPvb);
        }
    }

    private function createRole(string $name): void
    {
        $response = $this->getHttpClient()->postJSON(
            '/roles',
            $this->variableState->replaceVariables(
                Json::encode([
                    'name' => $name,
                ])
            )
        );
        $this->responseState->setResponse($response);

        $this->theResponseBodyShouldBeValidJson();
        $this->theResponseStatusShouldBe(201);
    }

    private function getRolesForUser(string $userId): void
    {
        $response = $this->getHttpClient()->get(
            '/users/' . $userId . '/roles',
        );
        $this->responseState->setResponse($response);

        $this->theResponseBodyShouldBeValidJson();
        $this->theResponseStatusShouldBe(200);
    }

    private function deleteARoleForUser(string $roleId, string $userId): void
    {
        $response = $this->getHttpClient()->delete(
            '/roles/' . $roleId . '/users/' . $userId,
        );
        $this->responseState->setResponse($response);

        $this->theResponseStatusShouldBe(204);
    }
}
