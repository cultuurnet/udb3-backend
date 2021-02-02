<?php
/**
 * @file
 */

namespace CultuurNet\UDB3;

use PHPUnit\Framework\TestCase;

class LabelTest extends TestCase
{
    /**
     * @test
     */
    public function it_refuses_value_that_are_not_strings()
    {
        $this->expectException(\InvalidArgumentException::class);

        new Label(null);
    }

    /**
     * @test
     */
    public function it_refuses_visible_that_is_not_a_boolean()
    {
        $this->expectException(\InvalidArgumentException::class);

        new Label('keyword 1', null);
    }

    /**
     * @test
     */
    public function it_refuses_value_with_length_less_than_three()
    {
        $this->expectException(\InvalidArgumentException::class);

        new Label('k');
    }

    /**
     * @test
     */
    public function it_refuses_value_with_length_longer_than_255()
    {
        $this->expectException(\InvalidArgumentException::class);

        new Label(
            'turnip greens yarrow ricebean rutabaga endive cauliflower sea lettuce kohlrabi amaranth water spinach avocado daikon napa cabbage asparagus winter purslane kale celery potato scallion desert raisin horseradish spinach carrot soko Lotus root water spinach fennel'
        );
    }

    /**
     * @test
     */
    public function it_accepts_a_regular_string_length_for_value()
    {
        $label = new Label('turnip');

        $this->assertEquals($label->__toString(), 'turnip');
    }
}
