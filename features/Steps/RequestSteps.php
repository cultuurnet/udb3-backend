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
        $this->requestState->setJson(
            $this->fixtures->loadJson($arg1, $this->variables)
        );
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

        $this->responseState->setResponse($response);
    }
}
