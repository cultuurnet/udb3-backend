<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\Commands;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use PHPUnit\Framework\TestCase;

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
     * @return AbstractCommand
     */
    abstract public function createCommand(UUID $uuid);
}
