<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Commands;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use PHPUnit\Framework\TestCase;

class RemoveConstraintTest extends TestCase
{
    private Uuid $uuid;

    private RemoveConstraint $removeConstraint;

    protected function setUp(): void
    {
        $this->uuid = new Uuid('45e61f58-e11b-4045-91a0-540e51c3a98d');

        $this->removeConstraint = new RemoveConstraint($this->uuid);
    }

    /**
     * @test
     */
    public function it_extends_an_abstract_command(): void
    {
        $this->assertTrue(is_subclass_of(
            $this->removeConstraint,
            AbstractCommand::class
        ));
    }

    /**
     * @test
     */
    public function it_stores_a_uuid(): void
    {
        $this->assertEquals($this->uuid, $this->removeConstraint->getUuid());
    }
}
