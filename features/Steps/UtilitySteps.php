<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Steps;

trait UtilitySteps
{
    private bool $initialPreventDuplicatePlaceCreationValue;

    /**
     * @Given I create a random name of :nrOfCharacters characters
     */
    public function iCreateARandomNameOfCharacters(int $nrOfCharacters): void
    {
        $this->variableState->setRandomVariable('name', $nrOfCharacters);
    }

    /**
     * @Given I create a random name of :nrOfCharacters characters and keep it as :variableName
     */
    public function iCreateARandomNameOfCharactersAndKeepItAs(int $nrOfCharacters, string $variableName): void
    {
        $this->variableState->setRandomVariable($variableName, $nrOfCharacters);
    }

    /**
     * @Given I create a random email and keep it as :variableName
     */
    public function iCreateARandomEmailAndKeepItAs(string $variableName): void
    {
        $this->variableState->setRandomEmail($variableName);
    }

    /**
     * @Given I wait :seconds seconds
     */
    public function iWaitSeconds(int $seconds): void
    {
        sleep($seconds);
    }

    /**
     * @Given /^I prevent duplicate place creation$/
     */
    public function iPreventDuplicatePlaceCreation(): void
    {
        $configFile = file_get_contents('config.php');

        if (str_contains($configFile, "'prevent_duplicate_places_creation' => true")) {
            // The config was already on true, so no further changes are required
            $this->initialPreventDuplicatePlaceCreationValue = true;
            return;
        }

        $configFile = str_replace(
            "'prevent_duplicate_places_creation' => false",
            "'prevent_duplicate_places_creation' => true",
            $configFile
        );

        file_put_contents('config.php', $configFile);

        $this->initialPreventDuplicatePlaceCreationValue = false;
    }

    /**
     * @Then /^I restore the duplicate configuration/
     */
    public function iRestoreTheDuplicateConfigurationOption(): void
    {
        $configFile = file_get_contents('config.php');

        $configFile = str_replace(
            "'prevent_duplicate_places_creation' => true",
            "'prevent_duplicate_places_creation' => " . ($this->initialPreventDuplicatePlaceCreationValue ? 'true' : 'false'),
            $configFile
        );

        file_put_contents('config.php', $configFile);
    }
}
