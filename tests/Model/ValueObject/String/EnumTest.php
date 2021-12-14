<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\String;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\MockString;

class EnumTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_throw_an_exception_if_an_invalid_value_is_given(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Encountered unknown value 'baz'. Allowed values: foo, bar");
        new MockEnum('baz');
    }

    /**
     * @test
     */
    public function it_should_be_comparable_to_other_enum_instances(): void
    {
        $foo = new MockEnum('foo');
        $bar = new MockEnum('bar');

        $this->assertTrue($foo->sameAs(new MockEnum('foo')));
        $this->assertFalse($foo->sameAs($bar));
    }

    /**
     * @test
     */
    public function it_should_not_be_comparable_to_other_string_value_objects(): void
    {
        $enum = new MockEnum('foo');
        $string = new MockString('foo');
        $this->assertFalse($enum->sameAs($string));
    }

    /**
     * @test
     */
    public function it_should_return_a_string_value(): void
    {
        $enum = new MockEnum('foo');
        $this->assertEquals('foo', $enum->toString());
    }

    /**
     * @test
     */
    public function it_should_be_constructable_by_a_static_call_to_the_enum_value_as_a_method(): void
    {
        $foo = MockEnum::foo();
        $bar = MockEnum::bar();

        $this->assertTrue($foo->sameAs(MockEnum::foo()));
        $this->assertFalse($foo->sameAs($bar));

        $this->assertEquals('foo', $foo->toString());
        $this->assertEquals('bar', $bar->toString());
    }
}
