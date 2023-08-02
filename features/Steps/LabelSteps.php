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
        // Create "public-visible" if it doesn't exist yet and (re)set the right privacy and visibility in case its needed
        $this->getLabel('public-visible');
        if ($this->responseState->getStatusCode() === 404) {
            $this->createLabel('public-visible', true, true);
        } else {
            $uuid = $this->responseState->getJsonContent()['uuid'];
            $this->iPatchTheLabelWithIdAndCommand($uuid, 'MakePublic');
            $this->iPatchTheLabelWithIdAndCommand($uuid, 'MakeVisible');
        }

        // Create "public-invisible" if it doesn't exist yet and (re)set the right privacy and visibility in case its needed
        $this->getLabel('public-invisible');
        if ($this->responseState->getStatusCode() === 404) {
            $this->createLabel('public-invisible', false, true);
        }
        $uuid = $this->responseState->getJsonContent()['uuid'];
        $this->iPatchTheLabelWithIdAndCommand($uuid, 'MakePublic');
        $this->iPatchTheLabelWithIdAndCommand($uuid, 'MakeInvisible');

        // Create "private-visible" if it doesn't exist yet and (re)set the right privacy and visibility in case its needed
        $this->getLabel('private-visible');
        if ($this->responseState->getStatusCode() === 404) {
            $this->createLabel('private-visible', true, false);
        }
        $uuid = $this->responseState->getJsonContent()['uuid'];
        $this->iPatchTheLabelWithIdAndCommand($uuid, 'MakePrivate');
        $this->iPatchTheLabelWithIdAndCommand($uuid, 'MakeVisible');

        // Create "private-invisible" if it doesn't exist yet and (re)set the right privacy and visibility in case its needed
        $this->getLabel('private-invisible');
        if ($this->responseState->getStatusCode() === 404) {
            $this->createLabel('private-invisible', false, false);
        }
        $uuid = $this->responseState->getJsonContent()['uuid'];
        $this->iPatchTheLabelWithIdAndCommand($uuid, 'MakePrivate');
        $this->iPatchTheLabelWithIdAndCommand($uuid, 'MakeInvisible');

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

        // Create "special_label#" if it doesn't exist yet and exclude it because of invalid #
        $this->getLabel('special_label#');
        if ($this->responseState->getStatusCode() === 404) {
            $this->createLabel('special_label#', true, true);
        }

        // Create "special_label*" if it doesn't exist yet and exclude it because of invalid #
        $this->getLabel('special_label*');
        if ($this->responseState->getStatusCode() === 404) {
            $this->createLabel('special_label*', true, true);
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
