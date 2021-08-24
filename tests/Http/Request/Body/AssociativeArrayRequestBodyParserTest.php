<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Request\Body;

use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use PHPUnit\Framework\TestCase;
use stdClass;

class AssociativeArrayRequestBodyParserTest extends TestCase
{
    private AssociativeArrayRequestBodyParser $associativeArrayRequestBodyParser;

    protected function setUp(): void
    {
        $this->associativeArrayRequestBodyParser = new AssociativeArrayRequestBodyParser();
    }

    /**
     * @test
     */
    public function it_converts_the_given_object_and_nested_objects(): void
    {
        $nestedObject = new stdClass();
        $nestedObject->string = 'mock';
        $nestedObject->bool = true;
        $nestedObject->int = 11;

        $nestedObjectInObject = clone $nestedObject;
        $nestedObjectInObject->object = $nestedObject;

        $given = (object) [
            'foo' => 'bar',
            'nested' => $nestedObject,
            'nestedInArray' => [$nestedObject],
            'nestedInArrayInArray' => [[$nestedObject]],
            'nestedInObject' => $nestedObjectInObject,
        ];

        $expected = [
            'foo' => 'bar',
            'nested' => [
                'string' => 'mock',
                'bool' => true,
                'int' => 11,
            ],
            'nestedInArray' => [
                [
                    'string' => 'mock',
                    'bool' => true,
                    'int' => 11,
                ],
            ],
            'nestedInArrayInArray' => [
                [
                    [
                        'string' => 'mock',
                        'bool' => true,
                        'int' => 11,
                    ],
                ],
            ],
            'nestedInObject' => [
                'string' => 'mock',
                'bool' => true,
                'int' => 11,
                'object' => [
                    'string' => 'mock',
                    'bool' => true,
                    'int' => 11,
                ],
            ],
        ];

        $request = (new Psr7RequestBuilder())
            ->build('PUT')
            ->withParsedBody($given);

        $actual = $this->associativeArrayRequestBodyParser->parse($request)->getParsedBody();

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_converts_nested_objects_inside_a_given_array(): void
    {
        $nestedObject = new stdClass();
        $nestedObject->string = 'mock';
        $nestedObject->bool = true;
        $nestedObject->int = 11;

        $given = [$nestedObject, $nestedObject];

        $expected = [
            [
                'string' => 'mock',
                'bool' => true,
                'int' => 11,
            ],
            [
                'string' => 'mock',
                'bool' => true,
                'int' => 11,
            ],
        ];

        $request = (new Psr7RequestBuilder())
            ->build('PUT')
            ->withParsedBody($given);

        $actual = $this->associativeArrayRequestBodyParser->parse($request)->getParsedBody();

        $this->assertEquals($expected, $actual);
    }
}
