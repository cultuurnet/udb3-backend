<?php

namespace CultuurNet\UDB3\Http\Assert;

use PHPUnit\Framework\TestCase;

class JsonEquals
{
    /**
     * @var TestCase
     */
    private $testCase;

    /**
     * @param TestCase $testCase
     */
    public function __construct(TestCase $testCase)
    {
        $this->testCase = $testCase;
    }

    /**
     * @param $expectedJson
     * @param $actualJson
     */
    public function assert($expectedJson, $actualJson)
    {
        $expected = json_decode($expectedJson, true);
        $actual = json_decode($actualJson, true);

        if (is_null($expected)) {
            $this->testCase->fail('Expected json is not valid json.');
        }

        if (is_null($actual)) {
            $this->testCase->fail('Actual json is not valid json.');
        }

        $this->testCase->assertEquals($expected, $actual);
    }
}
