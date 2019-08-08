<?php

namespace CultuurNet\UDB3\Http\Label;

use PHPUnit\Framework\TestCase;

class CommandTypeTest extends TestCase
{
    /**
     * @test
     */
    public function it_has_a_make_visible_option()
    {
        $commandType = CommandType::MAKE_VISIBLE();

        $this->assertEquals($commandType, CommandType::MAKE_VISIBLE);
    }

    /**
     * @test
     */
    public function it_has_a_make_invisible_option()
    {
        $commandType = CommandType::MAKE_INVISIBLE();

        $this->assertEquals($commandType, CommandType::MAKE_INVISIBLE);
    }

    /**
     * @test
     */
    public function it_has_a_make_public_option()
    {
        $commandType = CommandType::MAKE_PUBLIC();

        $this->assertEquals($commandType, CommandType::MAKE_PUBLIC);
    }

    /**
     * @test
     */
    public function it_has_a_make_private_option()
    {
        $commandType = CommandType::MAKE_PRIVATE();

        $this->assertEquals($commandType, CommandType::MAKE_PRIVATE);
    }

    /**
     * @test
     */
    public function it_has_only_four_specified_options()
    {
        $options = CommandType::getConstants();

        $this->assertEquals(
            [
                CommandType::MAKE_VISIBLE()->getName() => CommandType::MAKE_VISIBLE,
                CommandType::MAKE_INVISIBLE()->getName() => CommandType::MAKE_INVISIBLE,
                CommandType::MAKE_PUBLIC()->getName() => CommandType::MAKE_PUBLIC,
                CommandType::MAKE_PRIVATE()->getName() => CommandType::MAKE_PRIVATE,
            ],
            $options
        );
    }
}
