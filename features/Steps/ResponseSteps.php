<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Steps;

use Behat\Gherkin\Node\PyStringNode;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\RDF\NodeUri\CRC32HashGenerator;
use function PHPUnit\Framework\assertContains;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNotEquals;
use function PHPUnit\Framework\assertNull;
use function PHPUnit\Framework\assertStringContainsString;
use function PHPUnit\Framework\assertStringNotContainsString;
use function PHPUnit\Framework\assertTrue;

trait ResponseSteps
{
    /**
     * @Then the JSON response at :jsonPath should be :value
     */
    public function theJsonResponseAtShouldBe(string $jsonPath, string $value): void
    {
        $expected = $this->variableState->replaceVariables($value);

        if ($value === 'true' || $value === 'false') {
            $expected = $value === 'true';
        }

        if (is_numeric($value)) {
            $expected = (int) $value;
        }

        assertEquals(
            $expected,
            $this->responseState->getValueOnPath($jsonPath)
        );
    }

    /**
     * @Then the JSON response should be:
     */
    public function theJsonResponseShouldBe(PyStringNode $value): void
    {
        assertEquals(
            Json::decodeAssociatively($this->variableState->replaceVariables($value->getRaw())),
            $this->responseState->getJsonContent()
        );
    }

    /**
     * @Then the JSON response at :jsonPath should be:
     */
    public function theJsonResponseAtShouldBe2(string $jsonPath, PyStringNode $value): void
    {
        // TODO: Fix this workaround
        if (str_contains($jsonPath, 'videos/')) {
            $expectedVideo = Json::decodeAssociatively($this->variableState->replaceVariables($value->getRaw()));
            $actualVideo = $this->responseState->getValueOnPath($jsonPath);

            unset($expectedVideo['id']);
            unset($actualVideo['id']);

            assertEquals($expectedVideo, $actualVideo);
            return;
        }

        assertEquals(
            Json::decodeAssociatively($this->variableState->replaceVariables($value->getRaw())),
            $this->responseState->getValueOnPath($jsonPath)
        );
    }

    /**
     * @When the JSON response at :jsonPath should not be :value
     */
    public function theJsonResponseAtShouldNotBe(string $jsonPath, string $value): void
    {
        assertNotEquals(
            $this->variableState->replaceVariables($value),
            $this->responseState->getValueOnPath($jsonPath)
        );
    }

    /**
     * @Then the JSON response should include:
     */
    public function theJsonResponseShouldInclude(PyStringNode $value): void
    {
        assertStringContainsString(
            $this->variableState->replaceVariables($value->getRaw()),
            $this->responseState->getContent()
        );
    }

    /**
     * @Then the JSON response should not include:
     */
    public function theJsonResponseShouldNotInclude(PyStringNode $value): void
    {
        assertStringNotContainsString(
            $this->variableState->replaceVariables($value->getRaw()),
            $this->responseState->getContent()
        );
    }

    /**
     * @Then the JSON response at :url should include :value
     */
    public function theJsonResponseAtShouldInclude(string $jsonPath, string $value): void
    {
        $actual = $this->responseState->getValueOnPath($jsonPath);
        if ($jsonPath == '/') {
            $actual = $this->responseState->getContent();
        }

        if (is_array($actual)) {
            assertContains(
                $this->variableState->replaceVariables($value),
                $actual
            );
        } else {
            assertStringContainsString(
                $this->variableState->replaceVariables($value),
                $actual
            );
        }
    }

    /**
     * @Then the JSON response at :jsonPath should include:
     */
    public function theJsonResponseAtShouldInclude2(string $jsonPath, PyStringNode $value): void
    {
        $actual = $this->responseState->getValueOnPath($jsonPath);

        if (is_array($actual)) {
            assertContains(
                Json::decodeAssociatively($this->variableState->replaceVariables($value->getRaw())),
                $actual
            );
        } else {
            assertStringContainsString(
                $this->variableState->replaceVariables($value->getRaw()),
                $actual
            );
        }
    }

    /**
     * @Then the JSON response should not have :jsonPath
     */
    public function theJsonResponseShouldNotHave(string $jsonPath): void
    {
        assertNull($this->responseState->getValueOnPath($jsonPath));
    }

    /**
     * @Then the JSON response should have :jsonPath
     */
    public function theJsonResponseShouldHave(string $jsonPath): void
    {
        assertNotEquals(null, $this->responseState->getValueOnPath($jsonPath));
    }

    /**
     * @Then the JSON response at :jsonPath should have :nrOfEntries entries
     */
    public function theJsonResponseAtShouldHaveEntries(string $jsonPath, int $nrOfEntries): void
    {
        assertEquals(
            $nrOfEntries,
            count($this->responseState->getValueOnPath($jsonPath))
        );
    }

    /**
     * @Then the JSON response at :jsonPath should have :nrOfEntries entry
     */
    public function theJsonResponseAtShouldHaveEntry(string $jsonPath, int $nrOfEntries): void
    {
        $this->theJsonResponseAtShouldHaveEntries($jsonPath, $nrOfEntries);
    }

    /**
     * @Then the response body should be valid JSON
     */
    public function theResponseBodyShouldBeValidJson(): void
    {
        assertTrue($this->responseState->isValidJson());
    }

    /**
     * @Then the response status should be :statusCode
     */
    public function theResponseStatusShouldBe(int $statusCode): void
    {
        assertEquals($statusCode, $this->responseState->getStatusCode());
    }

    /**
     * @Then the content type should be :contentType
     */
    public function theContentTypeShouldBe(string $contentType): void
    {
        assertStringContainsString($contentType, $this->responseState->getContentType());
    }

    /**
     * @Then the body should be :body
     */
    public function theBodyShouldBe(string $body): void
    {
        assertEquals($body, $this->responseState->getContent());
    }

    /**
     * @Then I keep the value of the JSON response at :jsonPath as :variableName
     */
    public function iKeepTheValueOfTheJsonResponseAtAs(string $jsonPath, string $variableName): void
    {
        $this->variableState->setVariable(
            $variableName,
            $this->responseState->getValueOnPath($jsonPath)
        );
    }

    /**
     * @Then I keep the JSON response as :variableName
     */
    public function iKeepTheJsonResponseAs(string $variableName): void
    {
        $this->variableState->setVariable(
            $variableName,
            $this->responseState->getContent()
        );
    }


    /**
     * @When I save the identifier hash with the namespace :arg1 and id :arg2
     */
    public function iSaveTheIdentifierHashWithTheNamespaceAndId(string $namespace, string $localIdentifier): void
    {
        $this->variableState->setVariable('identifier', (new CRC32HashGenerator())->generate([
            'generiek:naamruimte' => $namespace,
            'generiek:lokaleIdentificator' => $this->variableState->getVariable($localIdentifier),
        ]));
    }


    /**
     * @Then the RDF response should match :fileName
     */
    public function theRdfResponseShouldMatch(string $fileName): void
    {
        assertEquals(
            $this->removeDates($this->fixtures->loadTurtle($fileName, $this->variableState)),
            $this->removeDates($this->responseState->getContent())
        );
    }

    /**
     * @Then the JSON response at :jsonPath is an online location
     */
    public function theJsonResponseAtIsAnOnlineLocation(string $jsonPath): void
    {
        assertEquals(
            $this->requestState->getBaseUrl() . '/place/' . Uuid::NIL,
            $this->responseState->getValueOnPath($jsonPath)
        );
    }


    /**
     * @Then show me the unparsed response
     */
    public function showMeTheUnparsedResponse(): void
    {
        echo $this->responseState->getContent();
    }

    private function removeDates(string $value): string
    {
        // Only remove the created and modified dates
        $datePattern = '/(?<=dcterms:created\s|dcterms:modified\s)"\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+\d{2}:\d{2}"/';
        return preg_replace($datePattern, '""', $value);
    }
}
