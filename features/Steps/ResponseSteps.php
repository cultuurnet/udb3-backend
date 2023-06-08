<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Steps;

use Behat\Gherkin\Node\PyStringNode;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNull;

trait ResponseSteps
{
    /**
     * @Then the JSON response at :jsonPath should be :variableName
     */
    public function theJsonResponseAtShouldBe(string $jsonPath, string $variableName): void
    {
        assertEquals(
            $this->variables->getVariable($variableName),
            $this->responseState->getValueOnPath($jsonPath)
        );
    }

    /**
     * @Then the JSON response at :jsonPath should be:
     */
    public function theJsonResponseAtShouldBe2(string $jsonPath, PyStringNode $value): void
    {
        assertEquals(
            json_decode($value->getRaw(), true),
            $this->responseState->getValueOnPath($jsonPath)
        );
    }

    /**
     * @Then the JSON response should not have :jsonPath
     */
    public function theJsonResponseShouldNotHave(string $jsonPath): void
    {
        assertNull($this->responseState->getValueOnPath($jsonPath));
    }

    /**
     * @Then the response status should be :statusCode
     */
    public function theResponseStatusShouldBe(int $statusCode)
    {
        assertEquals($statusCode, $this->responseState->getStatusCode());
    }

    /**
     * @Then the JSON response should be:
     */
    public function theJsonResponseShouldBe(PyStringNode $value)
    {
        $name = $this->variables->getVariable('name');
        $value = str_replace('%{name}', $name, $value->getRaw());
        assertEquals(json_decode($value, true), $this->responseState->getJsonContent());
    }

    /**
     * @Then the response body should be valid JSON
     */
    public function theResponseBodyShouldBeValidJson()
    {
        json_decode($this->responseState->getContent());
        assertEquals(JSON_ERROR_NONE, json_last_error());
    }

    /**
     * @Then I keep the value of the JSON response at :jsonPath as :variableName
     */
    public function iKeepTheValueOfTheJsonResponseAtAs(string $jsonPath, string $variableName)
    {
        $this->variables->addVariable(
            $variableName,
            $this->responseState->getValueOnPath($jsonPath)
        );
    }
}
