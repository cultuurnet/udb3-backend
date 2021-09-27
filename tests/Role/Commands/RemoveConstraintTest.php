<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Commands;

use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;

class RemoveConstraintTest extends TestCase
{
    private UUID $uuid;

    private RemoveConstraint $removeConstraint;

    protected function setUp()
    {
        $this->uuid = new UUID();

        $this->removeConstraint = new RemoveConstraint($this->uuid);
    }

    /**
     * @test
     */
    public function it_extends_an_abstract_command()
    {
        $this->assertTrue(is_subclass_of(
            $this->removeConstraint,
            AbstractCommand::class
        ));
    }

    /**
     * @test
     */
    public function it_stores_a_uuid()
    {
        $this->assertEquals($this->uuid, $this->removeConstraint->getUuid());
    }
}
