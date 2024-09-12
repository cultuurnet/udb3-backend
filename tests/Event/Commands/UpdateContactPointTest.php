<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Commands;

use CultuurNet\UDB3\ContactPoint;
use PHPUnit\Framework\TestCase;

class UpdateContactPointTest extends TestCase
{
    protected UpdateContactPoint $updateContactPoint;

    public function setUp(): void
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
    public function it_is_possible_to_instantiate_the_command_with_parameters(): void
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
