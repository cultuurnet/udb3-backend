<?php

namespace CultuurNet\UDB3\Steps;

use Behat\Gherkin\Node\PyStringNode;
use Psr\Http\Message\ResponseInterface;
use function PHPUnit\Framework\assertEquals;

trait Organizer
{
    private array $jsonResponse;

    /**
     * @Given I create a minimal organizer and save the :arg1 as :arg2
     */
    public function iCreateAMinimalOrganizerAndSaveTheAs($arg1, $arg2)
    {
        $organizer = $this->loadOrganizerWithRandomName('/organizers/organizer-minimal.json');

        $response = $this->getHttpClient()->postJSON(
            $this->baseUrl . '/organizers',
            $organizer
        );

        $this->storeResponseValue($response, $arg1, $arg2);
    }

    /**
     * @When I create an organizer from :arg1 and save the :arg2 as :arg3
     */
    public function iCreateAnOrganizerFromAndSaveTheAs($arg1, $arg2, $arg3)
    {
        $organizer = $this->loadOrganizerWithRandomName($arg1);

        $response = $this->getHttpClient()->postJSON(
            $this->baseUrl . '/organizers',
            $organizer
        );

        $this->storeResponseValue($response, $arg2, $arg3);
    }

    /**
     * @When I get the organizer at :arg1
     */
    public function iGetTheOrganizerAt($arg1)
    {
        $content = $this->getHttpClient()->getJSON($this->variables->getVariable($arg1));

        $this->jsonResponse = json_decode($content, true);
    }

    /**
     * @Then the JSON response at :arg1 should be :arg2
     */
    public function theJsonResponseAtShouldBe($arg1, $arg2)
    {
        assertEquals(
            $this->variables->getVariable($arg2),
            $this->getValueByPath($this->jsonResponse, $arg1)
        );
    }

    /**
     * @Then the JSON response at :arg1 should be:
     */
    public function theJsonResponseAtShouldBe2($arg1, PyStringNode $string)
    {
        assertEquals(
            json_decode($string->getRaw(), true),
            $this->jsonResponse[$arg1]
        );
    }

    private function loadOrganizerWithRandomName(string $filename): string
    {
        $organizer = file_get_contents(__DIR__ . '/../data/' . $filename);
        $name = $this->variables->addRandomVariable('name', 10);
        return str_replace('%{name}', $name, $organizer);
    }

    private function storeResponseValue(
        ResponseInterface $response,
        string $path,
        string $variableName
    ): void {
        $content = $response->getBody()->getContents();
        $json = json_decode($content, true);

        $this->variables->addVariable($variableName, $json[$path]);
    }

    private function getValueByPath($array, $path)
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
