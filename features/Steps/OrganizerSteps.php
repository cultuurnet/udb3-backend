<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Steps;

trait OrganizerSteps
{
    /**
     * @Given I create a minimal organizer and save the :jsonPath as :variableName
     */
    public function iCreateAMinimalOrganizerAndSaveTheAs($jsonPath, $variableName): void
    {
        $response = $this->getHttpClient()->postJSON(
            $this->requestState->getBaseUrl() . '/organizers',
            $this->fixtures->loadJsonWithRandomName('/organizers/organizer-minimal.json', $this->variables)
        );

        $this->responseState->setResponseAndStoreVariable($response, $this->variables, $jsonPath, $variableName);

        $this->theResponseStatusShouldBe(201);
        $this->theResponseBodyShouldBeValidJson();
    }

    /**
     * @When I create an organizer from :fileName and save the :jsonPath as :variableName
     */
    public function iCreateAnOrganizerFromAndSaveTheAs($fileName, $jsonPath, $variableName): void
    {
        $organizer = $this->fixtures->loadJsonWithRandomName($fileName, $this->variables);

        $response = $this->getHttpClient()->postJSON(
            $this->requestState->getBaseUrl() . '/organizers',
            $organizer
        );

        $this->responseState->setResponseAndStoreVariable($response, $this->variables, $jsonPath, $variableName);

        $this->theResponseStatusShouldBe(201);
        $this->theResponseBodyShouldBeValidJson();
    }

    /**
     * @When I update the organizer at :url from :fileName
     */
    public function iUpdateTheOrganizerAtFrom($url, $fileName): void
    {
        $this->getHttpClient()->putJSON(
            $this->variables->getVariable($url),
            $this->fixtures->loadJsonWithRandomName($fileName, $this->variables)
        );
    }

    /**
     * @When I get the organizer at :url
     */
    public function iGetTheOrganizerAt($url): void
    {
        $this->responseState->setResponse(
            $this->getHttpClient()->getJSON($this->variables->getVariable($url))
        );

        $this->theResponseStatusShouldBe(200);
        $this->theResponseBodyShouldBeValidJson();
    }
}
