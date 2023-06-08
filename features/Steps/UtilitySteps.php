<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Steps;

trait UtilitySteps
{
    /**
     * @Given I create a random name of :arg1 characters
     */
    public function iCreateARandomNameOfCharacters(int $arg1): void
    {
        $this->variables->addRandomVariable('name', $arg1);
    }
}
