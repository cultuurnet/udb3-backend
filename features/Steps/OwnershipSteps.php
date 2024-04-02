<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Steps;

trait OwnershipSteps
{
    /**
     * @When I request ownership for :ownerId on the organizer with organizerId :organizerId and save the :jsonPath as :variableName
     */
    public function iRequestOwnershipForOnTheOrganizerWithOrganizerIdAndSaveTheAs(
        string $ownerId,
        string $organizerId,
        string $jsonPath,
        string $variableName
    ): void {
        $this->requestOwnership(
            '/ownerships',
            $this->variableState->replaceVariables(json_encode([
                'itemId' => $organizerId,
                'itemType' => 'organizer',
                'ownerId' => $ownerId,
            ])),
            $jsonPath,
            $variableName
        );
    }

    /**
     * @When I approve the ownership with ownershipId :ownershipId
     */
    public function iApproveTheOwnershipWithOwnershipId(string $ownershipId): void
    {
        $response = $this->getHttpClient()->postJSON(
            '/ownerships/' . $this->variableState->replaceVariables($ownershipId) . '/approve',
            ''
        );
        $this->responseState->setResponse($response);

        $this->theResponseStatusShouldBe(202);
        $this->theResponseBodyShouldBeValidJson();
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
