<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Steps;

use CultuurNet\UDB3\Json;

trait RoleSteps
{
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
     * @Then I remove all roles for the current user
     */
    public function iRemoveAllRolesForTheCurrentUser(): void
    {
        throw new PendingException();
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
