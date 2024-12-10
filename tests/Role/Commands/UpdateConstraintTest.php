<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Commands;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Role\ValueObjects\Query;
use PHPUnit\Framework\TestCase;

class UpdateConstraintTest extends TestCase
{
    protected Uuid $uuid;

    protected Query $query;

    protected UpdateConstraint $updateConstraint;

    protected function setUp(): void
    {
        $this->uuid = new Uuid('f311378a-a34a-4d5f-ad49-a861f022ccb1');
        $this->query = new Query('city:3000');

        $this->updateConstraint = new UpdateConstraint(
            $this->uuid,
            $this->query
        );
    }

    /**
     * @test
     */
    public function it_extends_an_add_constraint_command(): void
    {
        $this->assertTrue(is_subclass_of(
            $this->updateConstraint,
            AddConstraint::class
        ));
    }
}
