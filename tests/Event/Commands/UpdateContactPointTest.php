<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Commands;

use CultuurNet\UDB3\Model\ValueObject\Contact\ContactPoint;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumber;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumbers;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddresses;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Model\ValueObject\Web\Urls;
use PHPUnit\Framework\TestCase;

class UpdateContactPointTest extends TestCase
{
    protected UpdateContactPoint $updateContactPoint;

    public function setUp(): void
    {
        $this->updateContactPoint = new UpdateContactPoint(
            'id',
            new ContactPoint(
                new TelephoneNumbers(new TelephoneNumber('0123456789')),
                new EmailAddresses(new EmailAddress('foo@bar.com')),
                new Urls(new Url('http://foo.bar'))
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
                new TelephoneNumbers(new TelephoneNumber('0123456789')),
                new EmailAddresses(new EmailAddress('foo@bar.com')),
                new Urls(new Url('http://foo.bar'))
            )
        );

        $this->assertEquals($expectedUpdateContactPoint, $this->updateContactPoint);
    }
}
