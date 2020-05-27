<?php

namespace CultuurNet\UDB3\Offer\Commands\Moderation;

use PHPUnit\Framework\TestCase;

abstract class AbstractModerationCommandTestBase extends TestCase
{
    /**
     * @test
     */
    public function it_is_an_abstract_moderation_command()
    {
        $this->assertTrue(is_subclass_of(
            $this->createModerationCommand(),
            AbstractModerationCommand::class
        ));
    }

    /**
     * @return string
     */
    abstract public function getModerationCommandClass();

    /**
     * @return AbstractModerationCommand
     */
    private function createModerationCommand()
    {
        /** @var AbstractModerationCommand $abstractModerationCommand */
        $abstractModerationCommand = $this->getMockForAbstractClass(
            $this->getModerationCommandClass(),
            [],
            "",
            false
        );

        return $abstractModerationCommand;
    }
}
