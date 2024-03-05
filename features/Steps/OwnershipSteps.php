<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Steps;

trait OwnershipSteps
{
    /**
     * @When I request ownership of the organizer with organizerId :organizerId and save the :jsonPath as :variableName
     */
    public function iRequestOwnershipOfTheOrganizerWithOrganizerIdAndSaveTheAs(string $organizerId, string $jsonPath, string $variableName): void
    {
        $this->requestOwnership(
            '/ownerships',
            $this->variableState->replaceVariables(json_encode([
                'itemId' => $organizerId,
                'itemType' => 'organizer',
                'ownerId' => 'auth0|631748dba64ea78e3983b207',
            ])),
            $jsonPath,
            $variableName
        );
    }

    /**
     * @When I get the ownership with ownershipId :ownershipId
     */
    public function iGetTheOwnershipWithOwnershipId(string $ownershipId): void
    {
        $this->responseState->setResponse(
            $this->getHttpClient()->get('/ownerships/' . $this->variableState->replaceVariables($ownershipId))
        );

        $this->theResponseStatusShouldBe(200);
        $this->theResponseBodyShouldBeValidJson();
    }

    private function requestOwnership(string $endpoint, string $json, string $jsonPath, string $variableName): void
    {
        $response = $this->getHttpClient()->postJSON(
            $endpoint,
            $json
        );
        $this->responseState->setResponse($response);

        $this->theResponseStatusShouldBe(201);
        $this->theResponseBodyShouldBeValidJson();
        $this->iKeepTheValueOfTheJsonResponseAtAs($jsonPath, $variableName);
    }
}
