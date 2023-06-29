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
}
