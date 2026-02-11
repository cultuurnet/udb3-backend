<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Steps;

trait OrganizerSteps
{
    /**
     * @When I create an organizer and save the :jsonPath as :variableName
     */
    public function iCreateAnOrganizerAndSaveTheAs(string $jsonPath, string $variableName): void
    {
        $this->createOrganizer(
            '/organizers',
            $this->requestState->getJson(),
            $jsonPath,
            $variableName
        );
    }

    /**
     * @Given I create a minimal organizer and save the :jsonPath as :variableName
     */
    public function iCreateAMinimalOrganizerAndSaveTheAs(string $jsonPath, string $variableName): void
    {
        $this->createOrganizer(
            '/organizers',
            $this->fixtures->loadJsonWithRandomName('organizers/organizer-minimal.json', $this->variableState),
            $jsonPath,
            $variableName
        );
    }

    /**
     * @When I create an organizer from :fileName and save the :jsonPath as :variableName
     */
    public function iCreateAnOrganizerFromAndSaveTheAs(string $fileName, string $jsonPath, string $variableName): void
    {
        $this->createOrganizer(
            '/organizers',
            $this->fixtures->loadJsonWithRandomName($fileName, $this->variableState),
            $jsonPath,
            $variableName
        );
    }

    /**
     * @When I import a new organizer from :fileName and save the :jsonPath as :variableName
     */
    public function iImportANewOrganizerFromAndSaveTheAs(string $fileName, string $jsonPath, string $variableName): void
    {
        $this->createOrganizer(
            '/imports/organizers',
            $this->fixtures->loadJsonWithRandomName($fileName, $this->variableState),
            $jsonPath,
            $variableName
        );
    }

    /**
     * @When I update the organizer at :url from :fileName
     */
    public function iUpdateTheOrganizerAtFrom(string $url, string $fileName): void
    {
        $this->responseState->setResponse(
            $this->getHttpClient()->putJSON(
                $url,
                $this->fixtures->loadJsonWithRandomName($fileName, $this->variableState)
            )
        );
    }

    /**
     * @When I update the organizer at :url
     */
    public function iUpdateTheOrganizerAt(string $url): void
    {
        $this->responseState->setResponse(
            $this->getHttpClient()->putJSON($url, $this->requestState->getJson())
        );
    }

    /**
     * @When I get the organizer at :url
     */
    public function iGetTheOrganizerAt(string $url): void
    {
        $this->responseState->setResponse(
            $this->getHttpClient()->get($url)
        );

        $this->theResponseStatusShouldBe(200);
        $this->theResponseBodyShouldBeValidJson();
    }

    /**
     * @When I get the RDF of organizer with id :id
     */
    public function iGetTheRdfOfOrganizerWithId(string $id): void
    {
        $this->responseState->setResponse(
            $this->getHttpClient()->getWithTimeout('/organizers/' . $id)
        );

        $this->theResponseStatusShouldBe(200);
    }

    /**
     * @When I delete the organizer at :url
     */
    public function iDeleteTheOrganizerAt(string $url): void
    {
        $this->responseState->setResponse(
            $this->getHttpClient()->delete($url)
        );

        $this->theResponseStatusShouldBe(204);
    }

    private function createOrganizer(string $endpoint, string $json, string $jsonPath, string $variableName): void
    {
        $response = $this->getHttpClient()->postJSON(
            $endpoint,
            $json
        );
        $this->responseState->setResponse($response);

        $this->theResponseStatusShouldBe(str_contains($endpoint, 'imports') ? 200 : 201);
        $this->theResponseBodyShouldBeValidJson();
        $this->iKeepTheValueOfTheJsonResponseAtAs($jsonPath, $variableName);

        $this->addScenarioLabelToResource('organizer');
    }
}
