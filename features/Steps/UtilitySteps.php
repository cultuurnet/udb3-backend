<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Steps;

use function PHPUnit\Framework\assertEquals;

trait UtilitySteps
{
    /**
     * @Given /^I create a name that includes special characters of elastic search and keep it as "([^"]*)"$/
     */
    public function iCreateANameThatIncludesSpecialCharactersOfElasticSearchAndKeepItAs(string $variableName): void
    {
        $this->variableState->setVariable($variableName, '(a)![a]' . uniqid('', true));
    }

    /**
     * @Given I create a random name of :nrOfCharacters characters
     */
    public function iCreateARandomNameOfCharacters(int $nrOfCharacters): void
    {
        $this->variableState->setRandomVariable('name', $nrOfCharacters);
    }

    /**
     * @Given I set the value of name to :value
     */
    public function iSetTheValueOfNameTo(string $value): void
    {
        $this->variableState->setVariable('name', $value);
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
     * @Given I store the count of the :type files in the :folderName folder
     */
    public function iStoreTheCountOfTheFilesInTheFolder(string $type, string $folderName): void
    {
        $result = $this->countFilesByType($type, $folderName);
        $this->variableState->setVariable('count', (string) $result);
    }

    /**
     * @Given I check if one :type file has been created in the :folderName folder
     */
    public function iCheckIfOneFileHasBeenCreatedInTheFolder(string $type, string $folderName): void
    {
        $original = (int) $this->variableState->getVariable('count');
        $result = $this->countFilesByType($type, $folderName);
        assertEquals($result, $original + 1);
    }

    private function countFilesByType(string $type, string $folder): int
    {
        $downloadsFolder = $this->config['folders'][$folder];
        return count(glob($downloadsFolder . '/*.' . $type));
    }
}
