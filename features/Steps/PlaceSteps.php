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
    public function iCreateAMinimalPlaceAndSaveTheAs($jsonPath, $variableName): void
    {
        $this->createPlace(
            '/places',
            $this->fixtures->loadJson('places/place-with-required-fields.json', $this->variableState),
            $jsonPath,
            $variableName
        );
    }

    /**
     * @Given I create a place from :fileName and save the :jsonPath as :variableName
     */
    public function iCreateAPlaceFromAndSaveTheAs(string $fileName, $jsonPath, $variableName): void
    {
        $this->createPlace(
            '/places',
            $this->fixtures->loadJson($fileName, $this->variableState),
            $jsonPath,
            $variableName
        );
    }

    /**
     * @Given I import a new place from :fileName and save the :jsonPath as :variableName
     */
    public function iImportANewPlaceFromAndSaveTheAs(string $fileName, $jsonPath, $variableName): void
    {
        $this->createPlace(
            '/imports/places',
            $this->fixtures->loadJson($fileName, $this->variableState),
            $jsonPath,
            $variableName
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
    public function iGetTheRdfOfPlaceWithId($id): void
    {
        $this->responseState->setResponse(
            $this->getHttpClient()->getWithTimeout('/places/' . $id)
        );

        $this->theResponseStatusShouldBe(200);
    }

    /**
     * @When I delete the place at :url
     */
    public function iDeleteThePlaceAt($url)
    {
        $this->responseState->setResponse(
            $this->getHttpClient()->delete($url)
        );

        $this->theResponseStatusShouldBe(204);
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
    public function iPublishThePlaceAtWithAvailableFrom($url, $availableFrom)
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
    public function iApproveThePlaceAt($url): void
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
    public function iApproveThePlaceViaLegacyPatchAt($url): void
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
    public function iRejectThePlaceWithReason($url, $reason): void
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
    public function iRejectThePlaceViaLegacyPatchAtWithReason($url, $reason): void
    {
        $this->requestState->setContentTypeHeader('application/ld+json;domain-model=Reject');

        $this->responseState->setResponse(
            $this->getHttpClient()->patchJSON($url, Json::encode(['reason' => $reason]))
        );

        $this->theResponseStatusShouldBe(204);
    }

    private function createPlace(string $endpoint, string $json, string $jsonPath, string $variableName): void
    {
        $response = $this->getHttpClient()->postJSON(
            $this->requestState->getBaseUrl() . $endpoint,
            $json
        );
        $this->responseState->setResponse($response);

        $this->theResponseStatusShouldBe(str_contains($endpoint, 'imports') ? 200 : 201);
        $this->theResponseBodyShouldBeValidJson();
        $this->iKeepTheValueOfTheJsonResponseAtAs($jsonPath, $variableName);
    }
}
