<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Steps;

use Behat\Gherkin\Node\PyStringNode;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNull;

trait ResponseSteps
{
    /**
     * @Then the JSON response at :arg1 should be :arg2
     */
    public function theJsonResponseAtShouldBe($arg1, $arg2): void
    {
        assertEquals(
            $this->variables->getVariable($arg2),
            $this->responseState->getValueOnPath($arg1)
        );
    }

    /**
     * @Then the JSON response at :arg1 should be:
     */
    public function theJsonResponseAtShouldBe2($arg1, PyStringNode $string): void
    {
        assertEquals(
            json_decode($string->getRaw(), true),
            $this->responseState->getValueOnPath($arg1)
        );
    }

    /**
     * @When the JSON response should not have :arg1
     */
    public function theJsonResponseShouldNotHave($arg1): void
    {
        assertNull($this->responseState->getValueOnPath($arg1));
    }

    /**
     * @Then the response status should be :arg1
     */
    public function theResponseStatusShouldBe($arg1)
    {
        assertEquals($arg1, $this->responseState->getStatusCode());
    }

    /**
     * @Then the JSON response should be:
     */
    public function theJsonResponseShouldBe(PyStringNode $string)
    {
        $name = $this->variables->getVariable('name');
        $string = str_replace('%{name}', $name, $string->getRaw());
        assertEquals(json_decode($string, true), $this->responseState->getJsonContent());
    }
}
