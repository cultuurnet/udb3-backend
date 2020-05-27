<?php

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\ContactPoint;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AbstractUpdateContactPointTest extends TestCase
{
    /**
     * @var AbstractUpdateContactPoint|MockObject
     */
    protected $updateContactPoint;

    /**
     * @var string
     */
    protected $itemId;

    /**
     * @var ContactPoint
     */
    protected $contactPoint;

    public function setUp()
    {
        $this->itemId = 'Foo';
        $this->contactPoint = new ContactPoint(
            array('0123456789'),
            array('foo@bar.com'),
            array('http://foo.bar')
        );

        $this->updateContactPoint = $this->getMockForAbstractClass(
            AbstractUpdateContactPoint::class,
            array($this->itemId, $this->contactPoint)
        );
    }

    /**
     * @test
     */
    public function it_can_return_its_properties()
    {
        $contactPoint = $this->updateContactPoint->getContactPoint();
        $expectedContactPoint = new ContactPoint(
            array('0123456789'),
            array('foo@bar.com'),
            array('http://foo.bar')
        );

        $this->assertEquals($expectedContactPoint, $contactPoint);

        $itemId = $this->updateContactPoint->getItemId();
        $expectedItemId = 'Foo';

        $this->assertEquals($expectedItemId, $itemId);
    }
}
