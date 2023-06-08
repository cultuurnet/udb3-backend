<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Steps;

trait RequestSteps
{
    /**
     * @Given I send and accept :type
     */
    public function iSendAndAccept($type): void
    {
        $this->requestState->setAcceptHeader($type);
        $this->requestState->setContentTypeHeader($type);
    }

    /**
     * @Given I set the JSON request payload from :fileName
     */
    public function iSetTheJsonRequestPayloadFrom($fileName)
    {
        $this->requestState->setJson(
            $this->fixtures->loadJson($fileName, $this->variables)
        );
    }

    /**
     * @When I send a POST request to :url
     */
    public function iSendAPostRequestTo($url)
    {
        $response = $this->getHttpClient()->postJSON(
            $url,
            $this->requestState->getJson()
        );

        $this->responseState->setResponse($response);
    }
}
