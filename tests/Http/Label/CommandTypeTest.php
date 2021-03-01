<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Label;

use PHPUnit\Framework\TestCase;

class CommandTypeTest extends TestCase
{
    /**
     * @test
     */
    public function it_has_a_make_visible_option()
    {
        $commandType = CommandType::makeVisible();

        $this->assertEquals($commandType, CommandType::MAKE_VISIBLE);
    }

    /**
     * @test
     */
    public function it_has_a_make_invisible_option()
    {
        $commandType = CommandType::makeInvisible();

        $this->assertEquals($commandType, CommandType::MAKE_INVISIBLE);
    }

    /**
     * @test
     */
    public function it_has_a_make_public_option()
    {
        $commandType = CommandType::makePublic();

        $this->assertEquals($commandType, CommandType::MAKE_PUBLIC);
    }

    /**
     * @test
     */
    public function it_has_a_make_private_option()
    {
        $commandType = CommandType::makePrivate();

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
                CommandType::makeVisible()->getName() => CommandType::MAKE_VISIBLE,
                CommandType::makeInvisible()->getName() => CommandType::MAKE_INVISIBLE,
                CommandType::makePublic()->getName() => CommandType::MAKE_PUBLIC,
                CommandType::makePrivate()->getName() => CommandType::MAKE_PRIVATE,
            ],
            $options
        );
    }
}
