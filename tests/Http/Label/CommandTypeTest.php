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

        $this->assertEquals($commandType->toString(), 'MakeVisible');
    }

    /**
     * @test
     */
    public function it_has_a_make_invisible_option()
    {
        $commandType = CommandType::makeInvisible();

        $this->assertEquals($commandType->toString(), 'MakeInvisible');
    }

    /**
     * @test
     */
    public function it_has_a_make_public_option()
    {
        $commandType = CommandType::makePublic();

        $this->assertEquals($commandType->toString(), 'MakePublic');
    }

    /**
     * @test
     */
    public function it_has_a_make_private_option()
    {
        $commandType = CommandType::makePrivate();

        $this->assertEquals($commandType->toString(), 'MakePrivate');
    }

    /**
     * @test
     */
    public function it_has_only_four_specified_options()
    {
        $options = CommandType::getAllowedValues();

        $this->assertEquals(
            [
                CommandType::makeVisible()->toString(),
                CommandType::makeInvisible()->toString(),
                CommandType::makePublic()->toString(),
                CommandType::makePrivate()->toString(),
            ],
            $options
        );
    }
}
