<?php

namespace CultuurNet\UDB3\Role\Commands;

use CultuurNet\UDB3\Role\ValueObjects\Query;
use CultuurNet\UDB3\ValueObject\SapiVersion;
use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;

class UpdateConstraintTest extends TestCase
{
    /**
     * @var UUID
     */
    protected $uuid;

    /**
     * @var SapiVersion
     */
    protected $sapiVersion;

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
        $this->uuid = new UUID();
        $this->sapiVersion = SapiVersion::V2();
        $this->query = new Query('city:3000');

        $this->updateConstraint = new UpdateConstraint(
            $this->uuid,
            $this->sapiVersion,
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
