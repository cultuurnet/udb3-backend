<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Steps;

use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;

trait RequestSteps
{
    /**
     * @Given I send and accept :type
     */
    public function iSendAndAccept(string $type): void
    {
        $this->requestState->setAcceptHeader($type);
        $this->requestState->setContentTypeHeader($type);
    }

    /**
     * @Given I set the JSON request payload from :fileName
     */
    public function iSetTheJsonRequestPayloadFrom(string $fileName): void
    {
        $this->requestState->setJson(
            $this->fixtures->loadJson($fileName, $this->variables)
        );
    }

    /**
     * @Given I set the JSON request payload to:
     */
    public function iSetTheJsonRequestPayloadTo(PyStringNode $jsonPayload): void
    {
        $this->requestState->setJson(
            $this->variables->replaceVariable($jsonPayload->getRaw())
        );
    }

    /**
     * @Given I set the form data properties to:
     */
    public function iSetTheFormDataPropertiesTo(TableNode $table)
    {
        $this->requestState->setForm($table->getRows());
    }

    /**
     * @When I send a POST request to :url
     */
    public function iSendAPostRequestTo(string $url): void
    {
        $response = $this->getHttpClient()->postJSON($url, $this->requestState->getJson());
        $this->responseState->setResponse($response);
    }

    /**
     * @When I send a PUT request to :url
     */
    public function iSendAPutRequestTo(string $url): void
    {
        $response = $this->getHttpClient()->putJSON($url, $this->requestState->getJson());
        $this->responseState->setResponse($response);
    }

    /**
     * @When I send a GET request to :url
     */
    public function iSendAGetRequestTo(string $url): void
    {
        $response = $this->getHttpClient()->getJSON($url);
        $this->responseState->setResponse($response);
    }

    /**
     * @When I send a PATCH request to :url
     */
    public function iSendAPatchRequestTo(string $url): void
    {
        $response = $this->getHttpClient()->patchJSON($url, $this->requestState->getJson());
        $this->responseState->setResponse($response);
    }

    /**
     * @When I send a DELETE request to :url
     */
    public function iSendADeleteRequestTo(string $url): void
    {
        $response = $this->getHttpClient()->delete($url);
        $this->responseState->setResponse($response);
    }

    /**
     * @When I upload :fileKey from path :filePath to :endpoint
     */
    public function iUploadFromPathTo($fileKey, $filePath, $endpoint)
    {
        $response = $this->getHttpClient()->postMultipart(
            $this->requestState->getBaseUrl() . $endpoint,
            $this->requestState->getForm(),
            $fileKey,
            $filePath,
        );
        $this->responseState->setResponse($response);
    }
}
