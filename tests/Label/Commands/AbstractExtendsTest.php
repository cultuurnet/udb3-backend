<?php

namespace CultuurNet\UDB3\Label\Commands;

use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;

abstract class AbstractExtendsTest extends TestCase
{
    /**
     * @var UUID
     */
    private $uuid;

    /**
     * @var AbstractCommand
     */
    private $command;

    protected function setUp()
    {
        $this->uuid = new UUID();

        $this->command = $this->createCommand($this->uuid);
    }

    /**
     * @test
     */
    public function it_extends_an_abstract_command()
    {
        $this->assertTrue(is_subclass_of(
            $this->command,
            AbstractCommand::class
        ));
    }

    /**
     * @param UUID $uuid
     * @return AbstractCommand
     */
    abstract public function createCommand(UUID $uuid);
}
