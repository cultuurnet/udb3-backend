<?php

use Behat\Behat\Context\Context;
use CultuurNet\UDB3\Support\Variables;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use function PHPUnit\Framework\assertEquals;

class FeatureContext implements Context
{
    private array $config;

    private string $baseUrl;
    private string $apiKey;
    private string $jwt;
    private string $acceptHeader;
    private string $contentTypeHeader;

    private array $jsonResponse;

    private Variables $variables;

    public function __construct()
    {
        $this->config = require_once(__DIR__ . '/../config.php');
        $this->variables = new Variables();
    }

    /**
     * @Given I am using the UDB3 base URL
     */
    public function iAmUsingTheUDB3BaseURL()
    {
        $this->baseUrl = $this->config['base_url'];
    }

    /**
     * @Given I am using an UiTID v1 API key of consumer :arg1
     */
    public function iAmUsingAnUitidV1ApiKeyOfConsumer($arg1)
    {
        $this->apiKey = $this->config['apiKeys'][$arg1];
    }

    /**
     * @Given I am authorized as JWT provider v1 user :arg1
     */
    public function iAmAuthorizedAsJwtProviderV1User($arg1)
    {
        $this->jwt = $this->config['users']['uitid_v1'][$arg1]['jwt'];
    }

    /**
     * @Given I send and accept :arg1
     */
    public function iSendAndAccept($arg1)
    {
        $this->acceptHeader = $arg1;
        $this->contentTypeHeader = $arg1;
    }

    /**
     * @Given I create a minimal organizer and save the :arg1 as :arg2
     */
    public function iCreateAMinimalOrganizerAndSaveTheAs($arg1, $arg2)
    {
        $organizer = file_get_contents(__DIR__ . '/../data/organizer/organizer-minimal.json');

        $name = $this->variables->addRandomVariable('name', 10);
        $organizer = str_replace('%{name}', $name, $organizer);

        $response = $this->postJSON(
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
        $content = $this->getJSON($this->variables->getVariable($arg1));

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

    public function getClient(): Client
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->jwt,
            'x-api-key' => $this->apiKey,
            'Content-Type' => $this->contentTypeHeader,
            'Accept' => $this->acceptHeader,
        ];

        return new Client([
            'base_uri' => $this->baseUrl,
            RequestOptions::HEADERS => $headers,
        ]);
    }

    public function postJSON(string $url, string $json): ResponseInterface
    {
        return $this->getClient()->post(
            $url,
            [
                RequestOptions::BODY => $json,
            ]
        );
    }

    public function getJSON(string $url): string
    {
        $response = $this->getClient()->get($url);

        return $response->getBody()->getContents();
    }
}
