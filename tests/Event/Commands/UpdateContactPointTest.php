<?php

namespace CultuurNet\UDB3\Event\Commands;

use CultuurNet\UDB3\ContactPoint;
use PHPUnit\Framework\TestCase;

class UpdateContactPointTest extends TestCase
{
    /**
     * @var UpdateContactPoint
     */
    protected $updateContactPoint;

    public function setUp()
    {
        $this->updateContactPoint = new UpdateContactPoint(
            'id',
            new ContactPoint(
                ['0123456789'],
                ['foo@bar.com'],
                ['http://foo.bar']
            )
        );
    }

    /**
     * @test
     */
    public function it_is_possible_to_instantiate_the_command_with_parameters()
    {
        $expectedUpdateContactPoint = new UpdateContactPoint(
            'id',
            new ContactPoint(
                ['0123456789'],
                ['foo@bar.com'],
                ['http://foo.bar']
            )
        );

        $this->assertEquals($expectedUpdateContactPoint, $this->updateContactPoint);
    }
}
