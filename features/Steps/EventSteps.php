<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Steps;

use CultuurNet\UDB3\Json;

trait EventSteps
{
    /**
     * @Given I create a minimal permanent event and save the :jsonPath as :variableName
     */
    public function iCreateAMinimalPermanentEventAndSaveTheAs(string $jsonPath, string $variableName): void
    {
        $this->createPlace(
            '/events',
            $this->fixtures->loadJson('/events/event-minimal-permanent.json', $this->variableState),
            $jsonPath,
            $variableName,
            201
        );
    }

    /**
     * @Given I create an event from :fileName and save the :jsonPath as :variableName
     */
    public function iCreateAnEventFromAndSaveTheAs(string $fileName, string $jsonPath, string $variableName): void
    {
        $this->createEvent(
            '/events',
            $this->fixtures->loadJson($fileName, $this->variableState),
            $jsonPath,
            $variableName
        );
    }

    /**
     * @When I update the event at :url from :fileName
     */
    public function iUpdateTheEventAtFrom(string $url, string $fileName): void
    {
        $this->responseState->setResponse(
            $this->getHttpClient()->putJSON(
                $url,
                $this->fixtures->loadJsonWithRandomName($fileName, $this->variableState)
            )
        );
    }

    /**
     * @Given I get the event at :url
     */
    public function iGetTheEventAt(string $url): void
    {
        $this->responseState->setResponse(
            $this->getHttpClient()->get($url)
        );

        $this->theResponseStatusShouldBe(200);
        $this->theResponseBodyShouldBeValidJson();
    }

    /**
     * @When I get the RDF of event with id :id
     */
    public function iGetTheRdfOfEventWithId(string $id): void
    {
        $this->responseState->setResponse(
            $this->getHttpClient()->getWithTimeout('/events/' . $id)
        );

        $this->theResponseStatusShouldBe(200);
    }

    /**
     * @When I delete the event at :url
     */
    public function iDeleteTheEventAt(string $url): void
    {
        $this->responseState->setResponse(
            $this->getHttpClient()->delete($url)
        );

        $this->theResponseStatusShouldBe(204);
    }

    /**
     * @When I publish the event at :url
     */
    public function iPublishTheEventAt(string $url): void
    {
        $this->responseState->setResponse(
            $this->getHttpClient()->putJSON(
                $url . '/workflow-status',
                Json::encode(['workflowStatus' => 'READY_FOR_VALIDATION'])
            )
        );

        $this->theResponseStatusShouldBe(204);
    }

    /**
     * @When I publish the event at :url with availableFrom :availableFrom
     */
    public function iPublishTheEventAtWithAvailableFrom(string $url, string $availableFrom): void
    {
        $this->responseState->setResponse(
            $this->getHttpClient()->putJSON(
                $url . '/workflow-status',
                Json::encode([
                    'workflowStatus' => 'READY_FOR_VALIDATION',
                    'availableFrom' => $availableFrom,
                ])
            )
        );

        $this->theResponseStatusShouldBe(204);
    }

    /**
     * @When I publish the event via legacy PATCH at :url
     */
    public function iPublishTheEventViaLegacyPatchAt(string $url): void
    {
        $this->requestState->setContentTypeHeader('application/ld+json;domain-model=Publish');

        $this->responseState->setResponse(
            $this->getHttpClient()->patchJSON($url, '')
        );

        $this->theResponseStatusShouldBe(204);
    }

    /**
     * @When I approve the event at :url
     */
    public function iApproveTheEventAt(string $url): void
    {
        $this->responseState->setResponse(
            $this->getHttpClient()->putJSON(
                $url . '/workflow-status',
                Json::encode(['workflowStatus' => 'APPROVED'])
            )
        );

        $this->theResponseStatusShouldBe(204);
    }

    /**
     * @When I approve the event via legacy PATCH at :url
     */
    public function iApproveTheEventViaLegacyPatchAt(string $url): void
    {
        $this->requestState->setContentTypeHeader('application/ld+json;domain-model=Approve');

        $this->responseState->setResponse(
            $this->getHttpClient()->patchJSON($url, '')
        );

        $this->theResponseStatusShouldBe(204);
    }

    /**
     * @When I reject the event at :url with reason :reason
     */
    public function iRejectTheEventWithReason(string $url, string $reason): void
    {
        $this->responseState->setResponse(
            $this->getHttpClient()->putJSON(
                $url . '/workflow-status',
                Json::encode([
                    'workflowStatus' => 'REJECTED',
                    'reason' => $reason,
                ])
            )
        );

        $this->theResponseStatusShouldBe(204);
    }

    /**
     * @When I reject the event via legacy PATCH at :url with reason :reason
     */
    public function iRejectTheEventViaLegacyPatchAtWithReason(string $url, string $reason): void
    {
        $this->requestState->setContentTypeHeader('application/ld+json;domain-model=Reject');

        $this->responseState->setResponse(
            $this->getHttpClient()->patchJSON($url, Json::encode(['reason' => $reason]))
        );

        $this->theResponseStatusShouldBe(204);
    }

    private function createEvent(string $endpoint, string $json, string $jsonPath, string $variableName): void
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
