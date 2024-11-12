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
     * @When I patch the label with id :id and command :command
     */
    public function iPatchTheLabelWithIdAndCommand(string $id, string $command): void
    {
        $response = $this->getHttpClient()->patchJSON(
            '/labels/' . $id,
            Json::encode([
                'command' => $command,
            ])
        );
        $this->responseState->setResponse($response);

        $this->theResponseStatusShouldBe(204);
    }

    /**
     * @When I create a label with name :name
     */
    public function iCreateALabelWithName(string $name): void
    {
        $this->createLabel(
            $this->variableState->replaceVariables($name),
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

    /**
     * @Given labels test data is available
     */
    public function labelsTestDataIsAvailable(): void
    {
        // Create test labels if needed
        // Create "public-visible" if it doesn't exist yet
        $this->getLabel('public-visible');
        if ($this->responseState->getStatusCode() === 404) {
            $this->createLabel('public-visible', true, true);
        }

        // Create "public-invisible" if it doesn't exist yet and set the right visibility
        $this->getLabel('public-invisible');
        if ($this->responseState->getStatusCode() === 404) {
            $this->createLabel('public-invisible', false, true);
            $uuid = $this->responseState->getJsonContent()['uuid'];
            $this->iPatchTheLabelWithIdAndCommand($uuid, 'MakeInvisible');
        }

        // Create "private-visible" if it doesn't exist yet and set the right privacy
        $this->getLabel('private-visible');
        if ($this->responseState->getStatusCode() === 404) {
            $this->createLabel('private-visible', true, false);
            $uuid = $this->responseState->getJsonContent()['uuid'];
            $this->iPatchTheLabelWithIdAndCommand($uuid, 'MakePrivate');
        }

        // Create "private-invisible" if it doesn't exist yet and set the right privacy and visibility
        $this->getLabel('private-invisible');
        if ($this->responseState->getStatusCode() === 404) {
            $this->createLabel('private-invisible', false, false);
            $uuid = $this->responseState->getJsonContent()['uuid'];
            $this->iPatchTheLabelWithIdAndCommand($uuid, 'MakePrivate');
            $this->iPatchTheLabelWithIdAndCommand($uuid, 'MakeInvisible');
        }

        // Create "special_label" if it doesn't exist yet
        $this->getLabel('special_label');
        if ($this->responseState->getStatusCode() === 404) {
            $this->createLabel('special_label', true, true);
        }

        // Create "special-label" if it doesn't exist yet
        $this->getLabel('special-label');
        if ($this->responseState->getStatusCode() === 404) {
            $this->createLabel('special-label', true, true);
        }

        // Create "special_label#" if it doesn't exist yet
        $this->getLabel('special_label#');
        if ($this->responseState->getStatusCode() === 404) {
            $this->createLabel('special_label#', true, true);
        }

        // Create "special_label*" if it doesn't exist yet
        $this->getLabel('special_label*');
        if ($this->responseState->getStatusCode() === 404) {
            $this->createLabel('special_label*', true, true);
        }

        // Create labels for sorting by match
        $this->getLabel('walk');
        if ($this->responseState->getStatusCode() === 404) {
            $this->createLabel('walk', true, true);
        }
        $this->getLabel('walking tour');
        if ($this->responseState->getStatusCode() === 404) {
            $this->createLabel('walking tour', true, true);
        }
        $this->getLabel('city walk');
        if ($this->responseState->getStatusCode() === 404) {
            $this->createLabel('city walk', true, true);
        }
        $this->getLabel('forest walk');
        if ($this->responseState->getStatusCode() === 404) {
            $this->createLabel('forest walk', true, true);
        }
    }

    private function createLabel(string $name, bool $visible, bool $public): void
    {
        $response = $this->getHttpClient()->postJSON(
            '/labels',
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

    private function getLabel(string $name): void
    {
        $response = $this->getHttpClient()->get(
            '/labels/' . urlencode($name)
        );
        $this->responseState->setResponse($response);

        $this->theResponseBodyShouldBeValidJson();
    }
}
