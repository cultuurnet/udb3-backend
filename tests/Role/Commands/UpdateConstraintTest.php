<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Commands;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Role\ValueObjects\Query;
use PHPUnit\Framework\TestCase;

class UpdateConstraintTest extends TestCase
{
    /**
     * @var UUID
     */
    protected $uuid;

    /**
     * @var Query
     */
    protected $query;

    /**
     * @var UpdateConstraint
     */
    protected $updateConstraint;

    protected function setUp()
    {
        $this->uuid = new UUID('f311378a-a34a-4d5f-ad49-a861f022ccb1');
        $this->query = new Query('city:3000');

        $this->updateConstraint = new UpdateConstraint(
            $this->uuid,
            $this->query
        );
    }

    /**
     * @test
     */
    public function it_extends_an_add_constraint_command()
    {
        $this->assertTrue(is_subclass_of(
            $this->updateConstraint,
            AddConstraint::class
        ));
    }
}
