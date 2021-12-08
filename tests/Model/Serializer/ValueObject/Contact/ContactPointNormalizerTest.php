<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Contact;

use CultuurNet\UDB3\Model\ValueObject\Contact\ContactPoint;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumber;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumbers;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddresses;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Model\ValueObject\Web\Urls;
use PHPUnit\Framework\TestCase;

final class ContactPointNormalizerTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_serialize(): void
    {
        $contactPoint = new ContactPoint(
            new TelephoneNumbers(
                new TelephoneNumber('02/551 18 70'),
                new TelephoneNumber('02/551 18 71')
            ),
            new EmailAddresses(
                new EmailAddress('info@publiq.be'),
                new EmailAddress('vragen@publiq.be')
            ),
            new Urls(
                new Url('https://www.publiq.be')
            )
        );

        $contactPointArray = [
            'phone' => [
                '02/551 18 70',
                '02/551 18 71',
            ],
            'email' => [
                'info@publiq.be',
                'vragen@publiq.be',
            ],
            'url' => [
                'https://www.publiq.be',
            ],
        ];

        $this->assertEquals(
            $contactPointArray,
            (new ContactPointNormalizer())->normalize($contactPoint)
        );
    }
}
