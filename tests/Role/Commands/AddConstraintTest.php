<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Commands;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use PHPUnit\Framework\TestCase;
use CultuurNet\UDB3\Role\ValueObjects\Query;

class AddConstraintTest extends TestCase
{
    protected Uuid $uuid;

    protected Query $query;

    protected AddConstraint $addConstraint;

    protected function setUp(): void
    {
        $this->uuid = new Uuid('7ec197c5-b816-43e1-b057-ba1d25a04567');
        $this->query = new Query('city:3000');

        $this->addConstraint = new AddConstraint(
            $this->uuid,
            $this->query
        );
    }

    /**
     * @test
     */
    public function it_extends_an_abstract_command(): void
    {
        $this->assertTrue(is_subclass_of(
            $this->addConstraint,
            AbstractCommand::class
        ));
    }

    /**
     * @test
     */
    public function it_stores_a_uuid(): void
    {
        $this->assertEquals($this->uuid, $this->addConstraint->getUuid());
    }

    /**
     * @test
     */
    public function it_stores_a_query(): void
    {
        $this->assertEquals($this->query, $this->addConstraint->getQuery());
    }
}
