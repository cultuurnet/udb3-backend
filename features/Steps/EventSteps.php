<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Steps;

trait EventSteps
{
    /**
     * @Given I create a minimal permanent event and save the :arg1 as :arg2
     */
    public function iCreateAMinimalPermanentEventAndSaveTheAs($jsonPath, $variableName): void
    {
        $response = $this->getHttpClient()->postJSON(
            $this->requestState->getBaseUrl() . '/events',
            $this->fixtures->loadJson('/events/event-minimal-permanent.json', $this->variableState)
        );
        $this->responseState->setResponse($response);

        $this->theResponseStatusShouldBe(201);
        $this->theResponseBodyShouldBeValidJson();
        $this->iKeepTheValueOfTheJsonResponseAtAs($jsonPath, $variableName);
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
}
