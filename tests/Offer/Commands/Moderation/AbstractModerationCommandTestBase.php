<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands\Moderation;

use PHPUnit\Framework\TestCase;

abstract class AbstractModerationCommandTestBase extends TestCase
{
    /**
     * @test
     */
    public function it_is_an_abstract_moderation_command(): void
    {
        $this->assertTrue(is_subclass_of(
            $this->createModerationCommand(),
            AbstractModerationCommand::class
        ));
    }

    abstract public function getModerationCommandClass(): string;

    private function createModerationCommand(): AbstractModerationCommand
    {
        /** @var AbstractModerationCommand $abstractModerationCommand */
        $abstractModerationCommand = $this->getMockForAbstractClass(
            $this->getModerationCommandClass(),
            [],
            '',
            false
        );

        return $abstractModerationCommand;
    }
}
