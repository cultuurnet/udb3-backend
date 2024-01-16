<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Steps;

trait UtilitySteps
{
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

    private bool $switchedPreventDuplicateCreation = false;

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

    private function changePreventDuplicateConfigFile(bool $bool): bool
    {
        $config = require 'config.php';

        if ($config['prevent_duplicate_creation'] === $bool) {
            return false;
        }

        $config['prevent_duplicate_creation'] = $bool;

        file_put_contents('config.php', '<?php' . PHP_EOL . PHP_EOL . 'return ' . $this->shorthandVarExport($config) . ';');

        return true;
    }

    // var_export uses old school array() style, this function converts this to the modern [] standard
    private function shorthandVarExport(array $expression): string
    {
        $export = var_export($expression, true);
        $patterns = [
            "/array \(/" => '[',
            "/^([ ]*)\)(,?)$/m" => '$1]$2',
        ];

        return preg_replace(array_keys($patterns), array_values($patterns), $export);
    }
}
