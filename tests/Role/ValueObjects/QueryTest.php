<?php

namespace CultuurNet\UDB3\Role\ValueObjects;

use PHPUnit\Framework\TestCase;

class QueryTest extends TestCase
{
    /**
     * @test
     * @param string $value
     * @dataProvider invalidDataProvider
     */
    public function it_should_throw_an_exception_when_query_is_empty(string $value): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Query can\'t be empty.');
        new Query($value);
    }

    /**
     * @return string[][]
     */
    public function invalidDataProvider(): array
    {
        return [
            [''],
            ['0'],
        ];
    }
}
