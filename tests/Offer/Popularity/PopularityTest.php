<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Popularity;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use TypeError;

class PopularityTest extends TestCase
{
    /**
     * @test
     */
    public function it_stores_an_int_value(): void
    {
        $popularity = new Popularity(123);

        $this->assertEquals(123, $popularity->toNative());
    }

    /**
     * @test
     */
    public function it_throws_on_invalid_values(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Popularity can\'t be smaller than zero.');

        new Popularity(-1);
    }

    /**
     * @test
     * @dataProvider inValidTypeProvider
     * @param mixed $value
     */
    public function it_throws_on_invalid_types($value): void
    {
        $this->expectException(TypeError::class);

        new Popularity($value);
    }

    public function inValidTypeProvider(): array
    {
        return [
            'string value' => ['123'],
            'float value' => [1.23],
        ];
    }
}
