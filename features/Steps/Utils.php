<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Steps;

trait Utils
{
    /**
     * @Given I create a random name of :arg1 characters
     */
    public function iCreateARandomNameOfCharacters(int $arg1): string
    {
        return $this->variables->addRandomVariable('name', $arg1);
    }
}
