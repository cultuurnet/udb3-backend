<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\Model\ValueObject\Contact\ContactPoint;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumber;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumbers;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddresses;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Model\ValueObject\Web\Urls;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AbstractUpdateContactPointTest extends TestCase
{
    /**
     * @var AbstractUpdateContactPoint&MockObject
     */
    protected $updateContactPoint;

    protected string $itemId;

    protected ContactPoint $contactPoint;

    public function setUp(): void
    {
        $this->itemId = 'Foo';
        $this->contactPoint = new ContactPoint(
            new TelephoneNumbers(new TelephoneNumber('0123456789')),
            new EmailAddresses(new EmailAddress('foo@bar.com')),
            new Urls(new Url('http://foo.bar'))
        );

        $this->updateContactPoint = $this->getMockForAbstractClass(
            AbstractUpdateContactPoint::class,
            [$this->itemId, $this->contactPoint]
        );
    }

    /**
     * @test
     */
    public function it_can_return_its_properties(): void
    {
        $contactPoint = $this->updateContactPoint->getContactPoint();
        $expectedContactPoint = new ContactPoint(
            new TelephoneNumbers(new TelephoneNumber('0123456789')),
            new EmailAddresses(new EmailAddress('foo@bar.com')),
            new Urls(new Url('http://foo.bar'))
        );

        $this->assertEquals($expectedContactPoint, $contactPoint);

        $itemId = $this->updateContactPoint->getItemId();
        $expectedItemId = 'Foo';

        $this->assertEquals($expectedItemId, $itemId);
    }
}
