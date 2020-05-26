<?php

namespace CultuurNet\UDB3\Role\Commands;

use CultuurNet\UDB3\ValueObject\SapiVersion;
use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;

class RemoveConstraintTest extends TestCase
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
     * @var RemoveConstraint
     */
    protected $removeConstraint;

    protected function setUp()
    {
        $this->uuid = new UUID();
        $this->sapiVersion = SapiVersion::V2();

        $this->removeConstraint = new RemoveConstraint(
            $this->uuid,
            $this->sapiVersion
        );
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

    /**
     * @test
     */
    public function it_stores_a_sapi_version()
    {
        $this->assertEquals($this->sapiVersion, $this->removeConstraint->getSapiVersion());
    }
}
