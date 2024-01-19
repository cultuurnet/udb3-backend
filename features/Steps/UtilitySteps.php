<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Steps;

trait UtilitySteps
{
    private bool $switchedPreventDuplicateCreation = false;

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
     * @Given /^I prevent duplicate creation$/
     */
    public function iPreventDuplicateCreation(): void
    {
        $this->switchedPreventDuplicateCreation = $this->changePreventDuplicateConfigFile(true);
    }

    /**
     * @Then /^I allow duplicate creation$/
     */
    public function iAllowDuplicateCreation(): void
    {
        if (!$this->switchedPreventDuplicateCreation) {
            return;
        }

        $this->changePreventDuplicateConfigFile(false);
    }

    private function changePreventDuplicateConfigFile(bool $changeValueTo): bool
    {
        $configFile = file_get_contents('config.php');

        //These values need to be strings for the search replace
        if ($changeValueTo) {
            $from = 'false';
            $to = 'true';
        } else {
            $from = 'true';
            $to = 'false';
        }

        $currentLine = "'prevent_duplicate_creation' => " . $from;
        $newLine = "'prevent_duplicate_creation' => " . $to;

        if (str_contains($configFile, $newLine)) {
            return false;
        }

        $configFile = str_replace($currentLine, $newLine, $configFile);

        file_put_contents('config.php', $configFile);

        return true;
    }
}
