<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTPAS\ValueObject;

use PHPUnit\Framework\TestCase;

class IdTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_not_throw_an_exception_for_a_non_empty_string(): void
    {
        $id = new Id('7d1a9104-a094-4d25-a8d0-fc20c1db243e');
        $this->assertEquals('7d1a9104-a094-4d25-a8d0-fc20c1db243e', $id->toNative());
    }

    /**
     * @test
     */
    public function it_should_not_throw_an_exception_for_a_casted_integer_gt_zero(): void
    {
        $id = new Id((string) 7);
        $this->assertEquals('7', $id->toNative());
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_for_a_string_with_zero_characters(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Id('');
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_for_a_string_with_zero_characters_after_trimming_spaces(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Id('    ');
    }
}
