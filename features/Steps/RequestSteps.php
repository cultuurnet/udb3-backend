<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Steps;

trait RequestSteps
{
    /**
     * @Given I send and accept :arg1
     */
    public function iSendAndAccept($arg1): void
    {
        $this->requestState->setAcceptHeader($arg1);
        $this->requestState->setContentTypeHeader($arg1);
    }

    /**
     * @Given I set the JSON request payload from :arg1
     */
    public function iSetTheJsonRequestPayloadFrom($arg1)
    {
        $organizer = file_get_contents(__DIR__ . '/../data/' . $arg1);
        $name = $this->variables->getVariable('name');
        $this->requestState->setJson(str_replace('%{name}', $name, $organizer));
    }

    /**
     * @When I send a POST request to :arg1
     */
    public function iSendAPostRequestTo($arg1)
    {
        $response = $this->getHttpClient()->postJSON(
            $arg1,
            $this->requestState->getJson()
        );

        $this->storeResponse($response);
    }
}
