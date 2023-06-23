<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Label;

use PHPUnit\Framework\TestCase;

class CommandTypeTest extends TestCase
{
    /**
     * @test
     */
    public function it_has_a_make_visible_option(): void
    {
        $commandType = CommandType::makeVisible();

        $this->assertEquals($commandType->toString(), 'MakeVisible');
    }

    /**
     * @test
     */
    public function it_has_a_make_invisible_option(): void
    {
        $commandType = CommandType::makeInvisible();

        $this->assertEquals($commandType->toString(), 'MakeInvisible');
    }

    /**
     * @test
     */
    public function it_has_a_make_public_option(): void
    {
        $commandType = CommandType::makePublic();

        $this->assertEquals($commandType->toString(), 'MakePublic');
    }

    /**
     * @test
     */
    public function it_has_a_make_private_option(): void
    {
        $commandType = CommandType::makePrivate();

        $this->assertEquals($commandType->toString(), 'MakePrivate');
    }

    /**
     * @test
     */
    public function it_has_an_include_option(): void
    {
        $commandType = CommandType::include();

        $this->assertEquals($commandType->toString(), 'Include');
    }

    /**
     * @test
     */
    public function it_has_a_exclude_option(): void
    {
        $commandType = CommandType::exclude();

        $this->assertEquals($commandType->toString(), 'Exclude');
    }

    /**
     * @test
     */
    public function it_has_only_six_specified_options(): void
    {
        $options = CommandType::getAllowedValues();

        $this->assertEquals(
            [
                CommandType::makeVisible()->toString(),
                CommandType::makeInvisible()->toString(),
                CommandType::makePublic()->toString(),
                CommandType::makePrivate()->toString(),
                CommandType::include()->toString(),
                CommandType::exclude()->toString(),
            ],
            $options
        );
    }
}
