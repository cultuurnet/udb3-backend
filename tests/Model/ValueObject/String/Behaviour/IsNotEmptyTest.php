<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\String\Behaviour;

class IsNotEmptyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @dataProvider stringValueProvider
     */
    public function it_should_throw_an_exception_if_an_empty_string_is_given(
        string $stringValue,
        bool $expectException
    ): void {
        if ($expectException) {
            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessage('Given string should not be empty.');
        }

        $vo = new MockNotEmptyString($stringValue);

        if (!$expectException) {
            $this->assertEquals($stringValue, $vo->toString());
        }
    }

    public function stringValueProvider(): array
    {
        return [
            [
                'stringValue' => '',
                'expectException' => true,
            ],
            [
                'stringValue' => '0',
                'expectException' => false,
            ],
            [
                'stringValue' => ' ',
                'expectException' => false,
            ],
            [
                'stringValue' => 'abc',
                'expectException' => false,
            ],
        ];
    }
}
