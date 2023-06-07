<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Steps;

trait Request
{
    private string $json = '';

    /**
     * @Given I set the JSON request payload from :arg1
     */
    public function iSetTheJsonRequestPayloadFrom($arg1)
    {
        $organizer = file_get_contents(__DIR__ . '/../data/' . $arg1);
        $name = $this->variables->getVariable('name');
        $this->json = str_replace('%{name}', $name, $organizer);
    }

    /**
     * @When I send a POST request to :arg1
     */
    public function iSendAPostRequestTo($arg1)
    {
        $response = $this->getHttpClient()->postJSON(
            $arg1,
            $this->json
        );

        $this->storeResponse($response);
    }
}