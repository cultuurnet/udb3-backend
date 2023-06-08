<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Steps;

trait PlaceSteps
{
    /**
     * @Given I create a place from :fileName and save the :jsonPath as :variableName
     */
    public function iCreateAPlaceFromAndSaveTheAs(string $fileName, $jsonPath, $variableName): void
    {
        $place = $this->fixtures->loadJson($fileName, $this->variables);

        $response = $this->getHttpClient()->postJSON(
            $this->requestState->getBaseUrl() . '/places',
            $place
        );
        $this->responseState->setResponse($response);

        $this->theResponseStatusShouldBe(201);
        $this->theResponseBodyShouldBeValidJson();
        $this->iKeepTheValueOfTheJsonResponseAtAs($jsonPath, $variableName);
    }

    /**
     * @Given I get the place at :url
     */
    public function iGetThePlaceAt(string $url): void
    {
        $this->responseState->setResponse(
            $this->getHttpClient()->getJSON($url)
        );

        $this->theResponseStatusShouldBe(200);
        $this->theResponseBodyShouldBeValidJson();
    }
}