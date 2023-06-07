<?php

namespace CultuurNet\UDB3\Steps;

use function PHPUnit\Framework\assertEquals;

trait Organizer
{
    private array $jsonResponse;

    /**
     * @Given I create a minimal organizer and save the :arg1 as :arg2
     */
    public function iCreateAMinimalOrganizerAndSaveTheAs($arg1, $arg2)
    {
        $organizer = file_get_contents(__DIR__ . '/../data/organizer/organizer-minimal.json');

        $name = $this->variables->addRandomVariable('name', 10);
        $organizer = str_replace('%{name}', $name, $organizer);

        $response = $this->getHttpClient()->postJSON(
            $this->baseUrl . '/organizers',
            $organizer
        );

        $content = $response->getBody()->getContents();
        $json = json_decode($content, true);

        $this->variables->addVariable($arg2, $json[$arg1]);
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
        $value = $this->variables->getVariable($arg2);

        assertEquals($value, $this->getValueByPath($this->jsonResponse, $arg1));
    }

    function getValueByPath($array, $path)
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
