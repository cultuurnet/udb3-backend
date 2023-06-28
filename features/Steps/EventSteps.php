<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Steps;

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
            $variableName
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
        $this->getHttpClient()->putJSON(
            $url,
            $this->fixtures->loadJsonWithRandomName($fileName, $this->variableState)
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
