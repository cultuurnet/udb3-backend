<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Steps;

use CultuurNet\UDB3\Json;

trait LabelSteps
{
    /**
     * @When I create a label with a random name of :nrOfCharacters characters
     */
    public function iCreateALabelWithARandomNameOfCharacters(int $nrOfCharacters): void
    {
        $this->iCreateARandomNameOfCharacters($nrOfCharacters);
        $this->createLabel(
            $this->variableState->getVariable('name'),
            true,
            true
        );
    }

    /**
     * @Given I create an invisible label with a random name of :nrOfCharacters characters
     */
    public function iCreateAnInvisibleLabelWithARandomNameOfCharacters(int $nrOfCharacters): void
    {
        $this->iCreateARandomNameOfCharacters($nrOfCharacters);
        $this->createLabel(
            $this->variableState->getVariable('name'),
            false,
            true
        );
    }

    private function createLabel(string $name, bool $visible, bool $public): void
    {
        $response = $this->getHttpClient()->postJSON(
            $this->requestState->getBaseUrl() . '/labels',
            $this->variableState->replaceVariables(
                Json::encode([
                    'name' => $name,
                    'visibility' => $visible ? 'visible' : 'invisible',
                    'privacy' => $public ? 'public' : 'private',
                ])
            )
        );
        $this->responseState->setResponse($response);

        $this->theResponseBodyShouldBeValidJson();
    }
}