<?php

namespace CultuurNet\UDB3\Role\Commands;

use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;
use CultuurNet\UDB3\Role\ValueObjects\Query;
use CultuurNet\UDB3\ValueObject\SapiVersion;

class AddConstraintTest extends TestCase
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
     * @var AddConstraint
     */
    protected $addConstraint;

    protected function setUp()
    {
        $this->uuid = new UUID();
        $this->sapiVersion = SapiVersion::V2();
        $this->query = new Query('city:3000');

        $this->addConstraint = new AddConstraint(
            $this->uuid,
            $this->sapiVersion,
            $this->query
        );
    }

    /**
     * @test
     */
    public function it_extends_an_abstract_command()
    {
        $this->assertTrue(is_subclass_of(
            $this->addConstraint,
            AbstractCommand::class
        ));
    }

    /**
     * @test
     */
    public function it_stores_a_uuid()
    {
        $this->assertEquals($this->uuid, $this->addConstraint->getUuid());
    }

    /**
     * @test
     */
    public function it_stores_a_sapi_version()
    {
        $this->assertEquals($this->sapiVersion, $this->addConstraint->getSapiVersion());
    }

    /**
     * @test
     */
    public function it_stores_a_query()
    {
        $this->assertEquals($this->query, $this->addConstraint->getQuery());
    }
}
