<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Request\Body;

use InvalidArgumentException;
use Opis\JsonSchema\Resolvers\SchemaResolver;
use Opis\JsonSchema\Uri;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class JsonSchemaLocatorTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_a_resolver_for_the_previously_set_schema_directory(): void
    {
        $directory = realpath(__DIR__ . '/../../../../vendor/publiq/udb3-json-schemas');

        $expectedResolver = new SchemaResolver();
        $expectedResolver->registerPrefix('file://' . $directory . '/', $directory);

        $actualResolver = JsonSchemaLocator::createSchemaResolver();

        $this->assertEquals($expectedResolver, $actualResolver);
    }

    /**
     * @test
     */
    public function it_returns_the_schema_uri_for_a_file(): void
    {
        $expected = Uri::create('file://' . realpath(__DIR__ . '/../../../../vendor/publiq/udb3-json-schemas/') . '/event-subEvent-patch.json');
        $actual = JsonSchemaLocator::createSchemaUri(JsonSchemaLocator::EVENT_SUB_EVENT_PATCH);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_does_not_accept_a_path_that_does_not_exist_as_schema_directory(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(__DIR__ . '/foo could not be found or is not a directory.');
        JsonSchemaLocator::setSchemaDirectory(__DIR__ . '/foo');
    }

    /**
     * @test
     */
    public function it_does_not_accept_a_file_as_schema_directory(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(__DIR__ . '/JsonSchemaLocatorTest.php could not be found or is not a directory.');
        JsonSchemaLocator::setSchemaDirectory(__DIR__ . '/JsonSchemaLocatorTest.php');
    }

    /**
     * @test
     */
    public function it_does_not_accept_a_file_name_that_is_not_a_constant_on_the_class_itself(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('mock.json is not in the list of known schema files, please use a predefined constant on the JsonSchemaLocator class (or add one).');
        JsonSchemaLocator::createSchemaUri('mock.json');
    }

    /**
     * @test
     */
    public function it_throws_if_the_requested_schema_does_not_exist_in_the_configured_schema_directory(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(__DIR__ . '/' . JsonSchemaLocator::EVENT_SUB_EVENT_PATCH . ' is not a file.');

        JsonSchemaLocator::setSchemaDirectory(__DIR__);
        JsonSchemaLocator::createSchemaUri(JsonSchemaLocator::EVENT_SUB_EVENT_PATCH);
    }
}
