<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Steps;

trait OrganizerSteps
{
    /**
     * @Given I create a minimal organizer and save the :arg1 as :arg2
     */
    public function iCreateAMinimalOrganizerAndSaveTheAs($arg1, $arg2): void
    {
        $response = $this->getHttpClient()->postJSON(
            $this->requestState->getBaseUrl() . '/organizers',
            $this->fixtures->loadJsonWithRandomName('/organizers/organizer-minimal.json', $this->variables)
        );

        $this->storeResponseValue($response, $arg1, $arg2);
    }

    /**
     * @When I create an organizer from :arg1 and save the :arg2 as :arg3
     */
    public function iCreateAnOrganizerFromAndSaveTheAs($arg1, $arg2, $arg3): void
    {
        $organizer = $this->fixtures->loadJsonWithRandomName($arg1, $this->variables);

        $response = $this->getHttpClient()->postJSON(
            $this->requestState->getBaseUrl() . '/organizers',
            $organizer
        );

        $this->storeResponseValue($response, $arg2, $arg3);
    }

    /**
     * @When I update the organizer at :arg1 from :arg2
     */
    public function iUpdateTheOrganizerAtFrom($arg1, $arg2): void
    {
        $this->getHttpClient()->putJSON(
            $this->variables->getVariable($arg1),
            $this->fixtures->loadJsonWithRandomName($arg2, $this->variables)
        );
    }

    /**
     * @When I get the organizer at :arg1
     */
    public function iGetTheOrganizerAt($arg1): void
    {
        $this->storeResponse(
            $this->getHttpClient()->getJSON($this->variables->getVariable($arg1))
        );
    }
}
