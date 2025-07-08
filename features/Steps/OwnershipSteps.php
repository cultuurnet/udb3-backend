<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Steps;

use CultuurNet\UDB3\Json;

trait OwnershipSteps
{
    /**
     * @When I request ownership for the current user on the organizer with organizerId :organizerId and save the :jsonPath as :variableName
     */
    public function iRequestOwnershipForTheCurrentUserOnTheOrganizerWithOrganizerIdAndSaveTheAs(
        string $organizerId,
        string $jsonPath,
        string $variableName
    ): void {
        $this->requestOwnership(
            '/ownerships',
            $this->variableState->replaceVariables(Json::encode([
                'itemId' => $organizerId,
                'itemType' => 'organizer',
            ])),
            $jsonPath,
            $variableName
        );
    }

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
            $this->variableState->replaceVariables(Json::encode([
                'itemId' => $organizerId,
                'itemType' => 'organizer',
                'ownerId' => $ownerId,
            ])),
            $jsonPath,
            $variableName
        );
    }

    /**
     * @When I request ownership for email :ownerEmail on the organizer with organizerId :organizerId and save the :jsonPath as :variableName
     */
    public function iRequestOwnershipForEmailOnTheOrganizerWithOrganizerIdAndSaveTheAs(
        string $ownerEmail,
        string $organizerId,
        string $jsonPath,
        string $variableName
    ): void {
        $this->requestOwnership(
            '/ownerships',
            $this->variableState->replaceVariables(Json::encode([
                'itemId' => $organizerId,
                'itemType' => 'organizer',
                'ownerEmail' => $ownerEmail,
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
        $response = $this->getHttpClient()->postEmpty(
            '/ownerships/' . $this->variableState->replaceVariables($ownershipId) . '/approve',
        );
        $this->responseState->setResponse($response);

        $this->theResponseStatusShouldBe(204);
    }

    /**
     * @When I reject the ownership with ownershipId :ownershipId
     */
    public function iRejectTheOwnershipWithOwnershipId(string $ownershipId): void
    {
        $response = $this->getHttpClient()->postEmpty(
            '/ownerships/' . $this->variableState->replaceVariables($ownershipId) . '/reject',
        );
        $this->responseState->setResponse($response);

        $this->theResponseStatusShouldBe(204);
    }

    /**
     * @When I delete the ownership with ownershipId :ownershipId
     */
    public function iDeleteTheOwnershipWithOwnershipId(string $ownershipId): void
    {
        $response = $this->getHttpClient()->delete(
            '/ownerships/' . $this->variableState->replaceVariables($ownershipId),
        );
        $this->responseState->setResponse($response);

        $this->theResponseStatusShouldBe(204);
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
