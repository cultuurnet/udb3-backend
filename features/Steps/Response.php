<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Steps;

use Behat\Gherkin\Node\PyStringNode;
use Psr\Http\Message\ResponseInterface;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNull;

trait Response
{
    private ResponseInterface $response;
    private int $status;
    private string $content;
    private array $jsonContent;

    /**
     * @Then the JSON response at :arg1 should be :arg2
     */
    public function theJsonResponseAtShouldBe($arg1, $arg2): void
    {
        assertEquals(
            $this->variables->getVariable($arg2),
            $this->getValueByPath($this->jsonContent, $arg1)
        );
    }

    /**
     * @Then the JSON response at :arg1 should be:
     */
    public function theJsonResponseAtShouldBe2($arg1, PyStringNode $string): void
    {
        assertEquals(
            json_decode($string->getRaw(), true),
            $this->jsonContent[$arg1]
        );
    }

    /**
     * @When the JSON response should not have :arg1
     */
    public function theJsonResponseShouldNotHave($arg1): void
    {
        assertNull($this->getValueByPath($this->jsonContent, $arg1));
    }

    /**
     * @Then the response status should be :arg1
     */
    public function theResponseStatusShouldBe($arg1)
    {
        assertEquals($arg1, $this->status);
    }

    /**
     * @Then the JSON response should be:
     */
    public function theJsonResponseShouldBe(PyStringNode $string)
    {
        $name = $this->variables->getVariable('name');
        $string = str_replace('%{name}', $name, $string->getRaw());
        assertEquals(json_decode($string, true), $this->jsonContent);
    }

    public function storeResponseValue(
        ResponseInterface $response,
        string $path,
        string $variableName
    ): void {
        $this->storeResponse($response);

        $this->variables->addVariable($variableName, $this->jsonContent[$path]);
    }

    public function storeResponse(ResponseInterface $response): void
    {
        $this->response = $response;
        $this->status = $response->getStatusCode();
        $this->content = $response->getBody()->getContents();
        $this->jsonContent = json_decode($this->content, true);
    }

    private function getValueByPath($array, $path): ?string
    {
        $parts = explode('/', $path);
        $value = $array;

        foreach ($parts as $part) {
            if (isset($value[$part])) {
                $value = $value[$part];
            } else {
                return null;
            }
        }

        return $value;
    }
}
