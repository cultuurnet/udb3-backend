<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\JSONLD;

use CultuurNet\UDB3\Http\JsonLdResponse;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\Url;

class ContextControllerTest extends TestCase
{
    /**
     * @var ContextController
     */
    private $controller;

    /**
     * @var StringLiteral
     */
    private $fileDirectory;

    public function setUp()
    {
        $this->fileDirectory = new StringLiteral(__DIR__ . '/');
        $this->controller = new ContextController($this->fileDirectory);
    }

    /**
     * @test
     */
    public function it_should_return_the_JSONLD_context_for_an_known_entity_type()
    {
        $contextResponse = $this->controller->get('event');

        $expectedResponse = new JsonLdResponse(
            json_decode(file_get_contents(__DIR__ . '/event.jsonld'))
        );

        $this->assertEquals($expectedResponse->getContent(), $contextResponse->getContent());
    }

    /**
     * @test
     */
    public function it_should_update_the_domain_reference_when_a_custom_base_path_is_set()
    {
        $path = Url::fromNative('https://du.de');
        $controllerWithCustomBasePath = $this->controller->withCustomBasePath($path);

        $contextResponse = $controllerWithCustomBasePath->get('event');

        $expectedResponse = new JsonLdResponse(
            json_decode(file_get_contents(__DIR__ . '/event-with-custom-base-path.jsonld'))
        );

        $this->assertEquals($expectedResponse->getContent(), $contextResponse->getContent());
    }

    /**
     * @test
     */
    public function it_accepts_custom_base_paths_with_a_trailing_slash()
    {
        $path = Url::fromNative('https://du.de/');
        $controllerWithCustomBasePath = $this->controller->withCustomBasePath($path);

        $contextResponse = $controllerWithCustomBasePath->get('event');

        $expectedResponse = new JsonLdResponse(
            json_decode(file_get_contents(__DIR__ . '/event-with-custom-base-path.jsonld'))
        );

        $this->assertEquals($expectedResponse->getContent(), $contextResponse->getContent());
    }
}
