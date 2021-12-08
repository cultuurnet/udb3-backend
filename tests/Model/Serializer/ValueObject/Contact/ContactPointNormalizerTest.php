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
                ...array_map(
                    fn (string $phone) => new TelephoneNumber($phone),
                    ['02/551 18 70', '02/551 18 71']
                )
            ),
            new EmailAddresses(
                ...array_map(
                    fn (string $email) => new EmailAddress($email),
                    ['info@publiq.be', 'vragen@publiq.be']
                )
            ),
            new Urls(
                ...array_map(
                    fn (string $url) => new Url($url),
                    ['https://www.publiq.be']
                )
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
