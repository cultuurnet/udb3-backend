<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Assert;

use PHPUnit\Framework\TestCase;

class JsonEquals
{
    /**
     * @var TestCase
     */
    private $testCase;


    public function __construct(TestCase $testCase)
    {
        $this->testCase = $testCase;
    }

    public function assert(string $expectedJson, string $actualJson): void
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
