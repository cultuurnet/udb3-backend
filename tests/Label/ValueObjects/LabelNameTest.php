<?php

namespace CultuurNet\UDB3\Label\ValueObjects;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ValueObjects\Exception\InvalidNativeArgumentException;

class LabelNameTest extends TestCase
{
    /**
     * @dataProvider labelNameValues
     * @test
     * @param mixed $value
     */
    public function it_refuses_value_that_are_not_strings($value)
    {
        $this->expectException(InvalidNativeArgumentException::class);

        new LabelName($value);
    }

    /**
     * @test
     */
    public function it_refuses_value_containing_a_semicolon()
    {
        $value = ';';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Value for argument $value should not contain semicolons.");

        new LabelName($value);
    }

    /**
     * @test
     */
    public function it_refuses_value_with_length_less_than_two()
    {
        $value = 'k';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Value for argument $value should not be shorter than 2 chars.");

        new LabelName($value);
    }

    /**
     * @test
     */
    public function it_refuses_value_with_length_longer_than_255()
    {
        $value = 'turnip greens yarrow ricebean rutabaga endive cauliflower sea lettuce kohlrabi amaranth water spinach avocado daikon napa cabbage asparagus winter purslane kale celery potato scallion desert raisin horseradish spinach carrot soko Lotus root water spinach fennel';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Value for argument $value should not be longer than 255 chars.");

        new LabelName($value);
    }

    /**
     * @test
     */
    public function it_accepts_a_regular_string_length_for_value()
    {
        $label = new LabelName('turnip');

        $this->assertEquals($label->toNative(), 'turnip');
    }

    /**
     * @test
     */
    public function it_trims_whitespace_from_the_value_passed_to_it()
    {
        $label = new LabelName('  turnip  ');

        $this->assertEquals($label->toNative(), 'turnip');
    }

    /**
     * @return array
     */
    public function labelNameValues()
    {
        return [
            [null],
            [1],
        ];
    }
}
