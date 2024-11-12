<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Steps;

use CultuurNet\UDB3\Json;

trait PlaceSteps
{
    /**
     * @When I create a place and save the :jsonPath as :variableName
     */
    public function iCreateAPlaceAndSaveTheAs(string $jsonPath, string $variableName): void
    {
        $this->createOrganizer(
            '/places',
            $this->requestState->getJson(),
            $jsonPath,
            $variableName
        );
    }

    /**
     * @Given I create a minimal place and save the :jsonPath as :variableName
     */
    public function iCreateAMinimalPlaceAndSaveTheAs(string $jsonPath, string $variableName): void
    {
        $this->createPlace(
            '/places',
            $this->fixtures->loadJson('places/place-with-required-fields.json', $this->variableState),
            $jsonPath,
            $variableName,
            201
        );
    }

    /**
     * @Given /^I create a minimal place and save the "([^"]*)" as "([^"]*)" then I should get a "([^"]*)" response code$/
     */
    public function iCreateAMinimalPlaceAndSaveTheAsThenIShouldGetAResponseCode(string $jsonPath, string $variableName, int $responseCode): void
    {
        $json = $this->fixtures->loadJson('places/place-with-required-fields-and-variable-name.json', $this->variableState);

        $this->createPlace(
            '/places',
            $json,
            $jsonPath,
            $variableName,
            $responseCode
        );
    }

    /**
     * @Given /^I create a minimal place then I should get a "([^"]*)" response code$/
     */
    public function iCreateAMinimalPlaceThenIShouldGetAResponseCode(int $responseCode): void
    {
        $json = $this->fixtures->loadJson('places/place-with-required-fields-and-variable-name.json', $this->variableState);

        $response = $this->getHttpClient()->postJSON(
            '/places',
            $json
        );
        $this->responseState->setResponse($response);

        $this->theResponseStatusShouldBe($responseCode);
        $this->theResponseBodyShouldBeValidJson();
    }

    /**
     * @Given I create a place from :fileName and save the :jsonPath as :variableName
     */
    public function iCreateAPlaceFromAndSaveTheAs(string $fileName, string $jsonPath, string $variableName): void
    {
        $this->createPlace(
            '/places',
            $this->fixtures->loadJson($fileName, $this->variableState),
            $jsonPath,
            $variableName,
            201
        );
    }

    /**
     * @Given I import a new place from :fileName and save the :jsonPath as :variableName
     */
    public function iImportANewPlaceFromAndSaveTheAs(string $fileName, string $jsonPath, string $variableName): void
    {
        $this->createPlace(
            '/imports/places',
            $this->fixtures->loadJson($fileName, $this->variableState),
            $jsonPath,
            $variableName,
            200
        );
    }

    /**
     * @When I update the place at :url
     */
    public function iUpdateThePlaceAt(string $url): void
    {
        $this->getHttpClient()->putJSON($url, $this->requestState->getJson());
    }

    /**
     * @When I update the place at :url from :fileName
     */
    public function iUpdateThePlaceAtFrom(string $url, string $fileName): void
    {
        $this->getHttpClient()->putJSON(
            $url,
            $this->fixtures->loadJsonWithRandomName($fileName, $this->variableState)
        );
    }

    /**
     * @Given I get the place at :url
     */
    public function iGetThePlaceAt(string $url): void
    {
        $this->responseState->setResponse(
            $this->getHttpClient()->get($url)
        );

        $this->theResponseStatusShouldBe(200);
        $this->theResponseBodyShouldBeValidJson();
    }

    /**
     * @When I get the RDF of place with id :id
     */
    public function iGetTheRdfOfPlaceWithId(string $id): void
    {
        $this->responseState->setResponse(
            $this->getHttpClient()->getWithTimeout('/places/' . $id)
        );

        $this->theResponseStatusShouldBe(200);
    }

    /**
     * @When I delete the place at :url
     */
    public function iDeleteThePlaceAt(string $url): void
    {
        $this->responseState->setResponse(
            $this->getHttpClient()->delete($url)
        );

        $this->theResponseStatusShouldBe(204);
    }

    /**
     * @When I delete the place at :url then the response status should be :responseStatus
     */
    public function iDeleteThePlaceAtThenTheResponseStatusShouldBe(string $url, int $responseStatus): void
    {
        $this->responseState->setResponse(
            $this->getHttpClient()->delete($url)
        );

        $this->theResponseStatusShouldBe($responseStatus);
    }

    /**
     * @When I publish the place at :url
     */
    public function iPublishThePlaceAt(string $url): void
    {
        $this->responseState->setResponse(
            $this->getHttpClient()->putJSON(
                $url . '/workflow-status',
                Json::encode(['workflowStatus' => 'READY_FOR_VALIDATION'])
            )
        );

        $this->theResponseStatusShouldBe(204);
    }

    /**
     * @When I publish the place at :url with availableFrom :availableFrom
     */
    public function iPublishThePlaceAtWithAvailableFrom(string $url, string $availableFrom): void
    {
        $this->responseState->setResponse(
            $this->getHttpClient()->putJSON(
                $url . '/workflow-status',
                Json::encode([
                    'workflowStatus' => 'READY_FOR_VALIDATION',
                    'availableFrom' => $availableFrom,
                ])
            )
        );

        $this->theResponseStatusShouldBe(204);
    }

    /**
     * @When I publish the place via legacy PATCH at :url
     */
    public function iPublishThePlaceViaLegacyPatchAt(string $url): void
    {
        $this->requestState->setContentTypeHeader('application/ld+json;domain-model=Publish');

        $this->responseState->setResponse(
            $this->getHttpClient()->patchJSON($url, '')
        );

        $this->theResponseStatusShouldBe(204);
    }

    /**
     * @When I approve the place at :url
     */
    public function iApproveThePlaceAt(string $url): void
    {
        $this->responseState->setResponse(
            $this->getHttpClient()->putJSON(
                $url . '/workflow-status',
                Json::encode(['workflowStatus' => 'APPROVED'])
            )
        );

        $this->theResponseStatusShouldBe(204);
    }

    /**
     * @When I approve the place via legacy PATCH at :url
     */
    public function iApproveThePlaceViaLegacyPatchAt(string $url): void
    {
        $this->requestState->setContentTypeHeader('application/ld+json;domain-model=Approve');

        $this->responseState->setResponse(
            $this->getHttpClient()->patchJSON($url, '')
        );

        $this->theResponseStatusShouldBe(204);
    }

    /**
     * @When I reject the place at :url with reason :reason
     */
    public function iRejectThePlaceWithReason(string $url, string $reason): void
    {
        $this->responseState->setResponse(
            $this->getHttpClient()->putJSON(
                $url . '/workflow-status',
                Json::encode([
                    'workflowStatus' => 'REJECTED',
                    'reason' => $reason,
                ])
            )
        );

        $this->theResponseStatusShouldBe(204);
    }

    /**
     * @When I reject the place via legacy PATCH at :url with reason :reason
     */
    public function iRejectThePlaceViaLegacyPatchAtWithReason(string $url, string $reason): void
    {
        $this->requestState->setContentTypeHeader('application/ld+json;domain-model=Reject');

        $this->responseState->setResponse(
            $this->getHttpClient()->patchJSON($url, Json::encode(['reason' => $reason]))
        );

        $this->theResponseStatusShouldBe(204);
    }

    private function createPlace(string $endpoint, string $json, string $jsonPath, string $variableName, int $responseStatus): void
    {
        $response = $this->getHttpClient()->postJSON(
            $endpoint,
            $json
        );
        $this->responseState->setResponse($response);

        $this->theResponseStatusShouldBe($responseStatus);
        $this->theResponseBodyShouldBeValidJson();
        $this->iKeepTheValueOfTheJsonResponseAtAs($jsonPath, $variableName);
    }
}
