<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Steps;

trait OrganizerSteps
{
    /**
     * @Given I create a minimal organizer and save the :jsonPath as :variableName
     */
    public function iCreateAMinimalOrganizerAndSaveTheAs(string $jsonPath, string $variableName): void
    {
        $response = $this->getHttpClient()->postJSON(
            $this->requestState->getBaseUrl() . '/organizers',
            $this->fixtures->loadJsonWithRandomName('/organizers/organizer-minimal.json', $this->variableState)
        );
        $this->responseState->setResponse($response);

        $this->theResponseStatusShouldBe(201);
        $this->theResponseBodyShouldBeValidJson();
        $this->iKeepTheValueOfTheJsonResponseAtAs($jsonPath, $variableName);
    }

    /**
     * @When I create an organizer from :fileName and save the :jsonPath as :variableName
     */
    public function iCreateAnOrganizerFromAndSaveTheAs(string $fileName, string $jsonPath, string $variableName): void
    {
        $organizer = $this->fixtures->loadJsonWithRandomName($fileName, $this->variableState);

        $response = $this->getHttpClient()->postJSON(
            $this->requestState->getBaseUrl() . '/organizers',
            $organizer
        );
        $this->responseState->setResponse($response);

        $this->theResponseStatusShouldBe(201);
        $this->theResponseBodyShouldBeValidJson();
        $this->iKeepTheValueOfTheJsonResponseAtAs($jsonPath, $variableName);
    }

    /**
     * @When I import a new organizer from :fileName and save the :jsonPath as :variableName
     */
    public function iImportANewOrganizerFromAndSaveTheAs(string $fileName, string $jsonPath, string $variableName): void
    {
        $organizer = $this->fixtures->loadJsonWithRandomName($fileName, $this->variableState);

        $response = $this->getHttpClient()->postJSON(
            $this->requestState->getBaseUrl() . '/imports/organizers',
            $organizer
        );
        $this->responseState->setResponse($response);

        $this->theResponseStatusShouldBe(200);
        $this->theResponseBodyShouldBeValidJson();
        $this->iKeepTheValueOfTheJsonResponseAtAs($jsonPath, $variableName);
    }

    /**
     * @When I create an organizer and save the :jsonPath as :variableName
     */
    public function iCreateAnOrganizerAndSaveTheAs(string $jsonPath, string $variableName): void
    {
        $response = $this->getHttpClient()->postJSON(
            $this->requestState->getBaseUrl() . '/imports/organizers',
            $this->requestState->getJson()
        );
        $this->responseState->setResponse($response);

        $this->theResponseStatusShouldBe(200);
        $this->theResponseBodyShouldBeValidJson();
        $this->iKeepTheValueOfTheJsonResponseAtAs($jsonPath, $variableName);
    }

    /**
     * @When I update the organizer at :url from :fileName
     */
    public function iUpdateTheOrganizerAtFrom(string $url, string $fileName): void
    {
        $this->getHttpClient()->putJSON(
            $url,
            $this->fixtures->loadJsonWithRandomName($fileName, $this->variableState)
        );
    }

    /**
     * @When I update the organizer at :url
     */
    public function iUpdateTheOrganizerAt(string $url): void
    {
        $this->getHttpClient()->putJSON($url, $this->requestState->getJson());
    }

    /**
     * @When I get the organizer at :url
     */
    public function iGetTheOrganizerAt(string $url): void
    {
        $this->responseState->setResponse(
            $this->getHttpClient()->getJSON($url)
        );

        $this->theResponseStatusShouldBe(200);
        $this->theResponseBodyShouldBeValidJson();
    }

    /**
     * @When I delete the organizer at :url
     */
    public function iDeleteTheOrganizerAt($url)
    {
        $this->responseState->setResponse(
            $this->getHttpClient()->delete($url)
        );

        $this->theResponseStatusShouldBe(204);
    }
}
